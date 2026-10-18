<?php

namespace App\Services\Impor;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\SheetProtection;
use OpenSpout\Writer\XLSX\Options\WorkbookProtection;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accounts from an Excel sheet. Each new user gets an email link to set their
 * own password, so no password is ever written in the file.
 */
class ImporPengguna extends Impor
{
    public function judul(): string
    {
        return 'Impor Pengguna';
    }

    public function ruteKembali(): string
    {
        return 'sistem.pengguna.index';
    }

    public function kolom(): array
    {
        return ['nama', 'email', 'peran'];
    }

    public function contoh(): array
    {
        return [
            ['Budi Santoso, M.Kom.', 'budi@kampus.ac.id', 'dosen'],
            ['Siti Aminah, M.T.', 'siti@kampus.ac.id', 'dosen, reviewer'],
        ];
    }

    public function jenisBerkas(): array
    {
        return ['xlsx'];
    }

    /** An xlsx file is a zip archive; some systems report it only as that. */
    public function aturanBerkas(): array
    {
        return ['extensions:xlsx', 'mimetypes:application/zip,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    }

    public function petunjuk(): string
    {
        return 'Isi sheet Pengguna pada templat Excel, satu baris per akun. Peran ditulis dengan kode atau nama peran, '
            .'dipisah koma bila lebih dari satu. Email yang sudah terdaftar ditolak. Setiap akun baru menerima email '
            .'berisi tautan untuk membuat kata sandi sendiri.';
    }

    /**
     * Reads the first sheet; the header row gives the keys.
     */
    public function baca(UploadedFile $berkas): array
    {
        $reader = new Reader;
        try {
            $reader->open($berkas->getRealPath());
        } catch (OpenSpoutException) {
            throw ValidationException::withMessages(['berkas' => 'Berkas bukan file Excel yang bisa dibaca. Gunakan templat.']);
        }
        $hasil = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $kepala = null;
                foreach ($sheet->getRowIterator() as $row) {
                    $sel = array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $row->toArray());
                    if ($kepala === null) {
                        $kepala = array_map('strtolower', $sel);

                        continue;
                    }
                    if (implode('', $sel) === '') {
                        continue;
                    }
                    $hasil[] = array_combine($kepala, array_pad(array_slice($sel, 0, count($kepala)), count($kepala), ''));
                }
                break;
            }
        } finally {
            $reader->close();
        }

        return $hasil;
    }

    public function periksa(array $baris): array
    {
        $emailAda = User::pluck('email')->map(fn ($e) => strtolower($e))->flip();
        $diFile = [];

        return array_map(function (array $b) use ($emailAda, &$diFile) {
            $galat = [];
            $email = strtolower($b['email'] ?? '');
            $b['email'] = $email;
            if (($b['nama'] ?? '') === '') {
                $galat[] = 'Nama kosong.';
            } elseif (mb_strlen($b['nama']) > 255) {
                $galat[] = 'Nama paling panjang 255 karakter.';
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                $galat[] = 'Email tidak valid.';
            } elseif ($emailAda->has($email)) {
                $galat[] = 'Email sudah terdaftar.';
            } elseif (isset($diFile[$email])) {
                $galat[] = 'Email muncul lebih dari sekali di berkas.';
            }
            $diFile[$email] = true;

            [$peran, $tidakDikenal] = self::bacaPeran($b['peran'] ?? '');
            if ($tidakDikenal) {
                $galat[] = 'Peran tidak dikenal: '.implode(', ', $tidakDikenal).'.';
            } elseif (! $peran) {
                $galat[] = 'Peran kosong.';
            }
            $b['peran'] = implode(', ', $peran);

            return ['data' => $b, 'aksi' => 'baru', 'galat' => $galat];
        }, $baris);
    }

    public function simpan(array $hasil): int
    {
        $baru = DB::transaction(fn () => array_map(function (array $b) {
            $user = User::create(['name' => $b['nama'], 'email' => $b['email'], 'password' => Str::random(40)]);
            $user->markEmailAsVerified();
            $user->roles()->sync(Role::whereIn('kode', self::bacaPeran($b['peran'])[0])->pluck('id'));

            return $user;
        }, array_column($hasil, 'data')));

        foreach ($baru as $user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return count($baru);
    }

    /**
     * An Excel template: the Pengguna sheet to fill in, and locked sheets with
     * the instructions and the role codes. Sheets cannot be added, renamed or removed.
     */
    public function unduhTemplat(string $jenis): Response
    {
        $kunci = substr(hash('sha256', 'templat-impor|'.config('app.key')), 0, 20);
        $options = new Options;
        $options->setWorkbookProtection(new WorkbookProtection(password: $kunci, lockStructure: true));
        $writer = new Writer($options);
        $berkas = tempnam(sys_get_temp_dir(), 'templat');
        $writer->openToFile($berkas);
        $tebal = (new Style)->setFontBold();

        $sheet = $writer->getCurrentSheet()->setName('Pengguna');
        $sheet->setColumnWidth(35, 1);
        $sheet->setColumnWidth(35, 2);
        $sheet->setColumnWidth(30, 3);
        $writer->addRow(Row::fromValues($this->kolom(), $tebal));
        foreach ($this->contoh() as $baris) {
            $writer->addRow(Row::fromValues($baris));
        }

        $terkunci = new SheetProtection(password: $kunci, lockSheet: true, lockObjects: true, lockScenarios: true);

        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk')->setSheetProtection($terkunci);
        $sheet->setColumnWidth(110, 1);
        $writer->addRow(Row::fromValues(['Cara mengisi'], $tebal));
        foreach ([
            '1. Isi sheet Pengguna mulai baris 2. Jangan mengubah judul kolom di baris 1.',
            '2. nama: nama lengkap beserta gelar.',
            '3. email: alamat email aktif; tautan untuk membuat kata sandi dikirim ke alamat ini.',
            '4. peran: kode atau nama peran dari sheet Daftar peran, dipisah koma bila lebih dari satu.',
            '5. Hapus baris contoh sebelum mengunggah. Email yang sudah terdaftar akan ditolak.',
            '6. Akun dengan peran pimpinan_unit perlu diatur unitnya di menu Pimpinan unit setelah diimpor.',
        ] as $teks) {
            $writer->addRow(Row::fromValues([$teks]));
        }

        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('Daftar peran')->setSheetProtection($terkunci);
        $sheet->setColumnWidth(20, 1);
        $sheet->setColumnWidth(25, 2);
        $writer->addRow(Row::fromValues(['kode', 'nama peran'], $tebal));
        foreach (Role::ALL as $kode => $nama) {
            $writer->addRow(Row::fromValues([$kode, $nama]));
        }

        $writer->setCurrentSheet($writer->getSheets()[0]);
        $writer->close();

        return response()->download($berkas, "templat-{$jenis}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Role codes from a cell such as "dosen, Reviewer", plus the words that match no role.
     *
     * @return array{list<string>, list<string>}
     */
    private static function bacaPeran(string $sel): array
    {
        $kenal = [];
        $asing = [];
        foreach (preg_split('/[,;]/', $sel) as $kata) {
            $kata = trim($kata);
            if ($kata === '') {
                continue;
            }
            $kode = collect(Role::ALL)->search(fn ($nama, $kode) => strcasecmp($kode, $kata) === 0 || strcasecmp($nama, $kata) === 0);
            if ($kode === false) {
                $asing[] = $kata;
            } elseif (! in_array($kode, $kenal, true)) {
                $kenal[] = $kode;
            }
        }

        return [$kenal, $asing];
    }
}
