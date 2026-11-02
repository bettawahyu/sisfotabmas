<?php

namespace App\Services\Impor;

use App\Enums\JenisKegiatan;
use App\Enums\TipeAnggota;
use App\Models\Dosen;
use App\Models\KabupatenKota;
use App\Models\Mitra;
use App\Models\RiwayatKegiatan;
use App\Models\User;
use App\Services\AlurRiwayat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\SheetProtection;
use OpenSpout\Writer\XLSX\Options\WorkbookProtection;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Old research or community service records from an Excel sheet, entered by
 * LPPM or an operator. Lecturers are found by NIDN (or NUPTK), the imported
 * records are valid at once like any LPPM entry, and new partners join the
 * register. Files never come through Excel: a finished activity is saved
 * without its final report and shows as not yet uploaded until someone adds
 * it through the form.
 */
class ImporRiwayat extends ImporExcel
{
    public function __construct(private JenisKegiatan $jenis, private User $oleh) {}

    public function judul(): string
    {
        return 'Impor '.AlurRiwayat::LABEL[AlurRiwayat::slug($this->jenis)];
    }

    public function ruteKembali(): string
    {
        return 'riwayat-dosen.index';
    }

    /** Each row is checked for duplicates against the database, so files stay small. */
    public function maksBaris(): int
    {
        return 500;
    }

    public function kolom(): array
    {
        return [
            'judul', 'tahun_mulai', 'tahun_selesai', 'status_kegiatan', 'sumber_dana', 'pemberi_dana', 'skema',
            'nilai_dana', 'no_kontrak', 'nidn_ketua', 'nidn_anggota', 'mahasiswa', 'mitra', 'kabupaten_kota',
            'alamat_lokasi', 'ringkasan',
            ...($this->pkm() ? ['jumlah_sasaran', 'catatan_dampak', 'tautan_video', 'tautan_media'] : []),
        ];
    }

    public function contoh(): array
    {
        $contoh = $this->pkm() ? [
            ['Pelatihan pemasaran daring bagi UMKM keripik', '2023', '2023', 'selesai', 'mandiri', '', '', '2500000', '',
                '0012345678', '0023456789', 'Ayu Lestari (2201001); Bagus Pratama (2201002)', 'UMKM Keripik Salak',
                'Kabupaten Sleman', 'Desa Wonokerto, Kecamatan Turi', 'Pelatihan pemasaran lewat lokapasar.',
                '25', 'Penjualan daring mitra naik.', 'https://youtu.be/contoh', 'https://koran.example/berita'],
        ] : [
            ['Pemetaan kualitas air sumur warga', '2022', '2023', 'selesai', 'kemdiktisaintek', 'DRTPM', 'Penelitian Dosen Pemula',
                '15000000', '123/E5/PG/2022', '0012345678', '0023456789, 0034567890', 'Ayu Lestari (2201001)', '',
                '3404', 'Kecamatan Ngemplak', 'Ringkasan singkat kegiatan.'],
        ];

        return $contoh;
    }

    public function petunjuk(): string
    {
        return 'Isi sheet Riwayat pada templat Excel, satu baris per kegiatan. Dosen dicari lewat NIDN atau NUPTK, '
            .'jadi pastikan dosen sudah terdaftar di Data Dosen. Data yang diimpor langsung tervalidasi, dan mitra '
            .'baru langsung masuk data master. Berkas laporan akhir tidak ikut diimpor: kegiatan selesai ditandai '
            .'"laporan belum diunggah" sampai dilengkapi lewat form. Paling banyak 500 baris per berkas.';
    }

    public function periksa(array $baris): array
    {
        $kamus = $this->kamus();
        $diFile = [];
        $nomor = 1;

        return array_map(function (array $b) use ($kamus, &$diFile, &$nomor) {
            $nomor++;
            $galat = [];
            $peringatan = [];
            $b = array_map(fn ($v) => trim((string) $v), $b);
            $r = [];

            $judul = $b['judul'] ?? '';
            if ($judul === '') {
                $galat[] = 'Judul kosong.';
            } elseif (mb_strlen($judul) > 500) {
                $galat[] = 'Judul paling panjang 500 karakter.';
            }

            $r['tahun_mulai'] = $this->tahun($b['tahun_mulai'] ?? '');
            if ($r['tahun_mulai'] === null) {
                $galat[] = 'Tahun mulai harus diisi tahun yang benar.';
            }
            $r['tahun_selesai'] = null;
            if (($b['tahun_selesai'] ?? '') !== '') {
                $r['tahun_selesai'] = $this->tahun($b['tahun_selesai']);
                if ($r['tahun_selesai'] === null || ($r['tahun_mulai'] && $r['tahun_selesai'] < $r['tahun_mulai'])) {
                    $galat[] = 'Tahun selesai tidak boleh sebelum tahun mulai.';
                }
            }

            $r['status_kegiatan'] = $this->kode($b['status_kegiatan'] ?? '', RiwayatKegiatan::STATUS_KEGIATAN + ['berlangsung' => 'berlangsung']);
            $r['status_kegiatan'] = $r['status_kegiatan'] === 'berlangsung' ? RiwayatKegiatan::BERJALAN : $r['status_kegiatan'];
            if ($r['status_kegiatan'] === null) {
                $galat[] = 'Status kegiatan harus selesai atau berjalan.';
            }
            $r['sumber_dana'] = $this->kode($b['sumber_dana'] ?? '', RiwayatKegiatan::SUMBER_DANA);
            if ($r['sumber_dana'] === null) {
                $galat[] = 'Sumber dana tidak dikenal; lihat sheet Daftar kode. Hibah LPPM tidak diimpor karena sudah tercatat dari proposal.';
            }
            foreach (['pemberi_dana', 'skema', 'no_kontrak', 'alamat_lokasi'] as $kolom) {
                if (mb_strlen($b[$kolom] ?? '') > 255) {
                    $galat[] = str_replace('_', ' ', ucfirst($kolom)).' paling panjang 255 karakter.';
                }
            }

            $r['nilai_dana'] = null;
            if (($b['nilai_dana'] ?? '') !== '') {
                $angka = preg_replace('/^rp\.?|\s|\.|,00$/i', '', $b['nilai_dana']);
                if (! ctype_digit($angka)) {
                    $galat[] = 'Nilai dana harus berupa angka rupiah.';
                } else {
                    $r['nilai_dana'] = (int) $angka;
                }
            }

            $ketua = $this->dosen($b['nidn_ketua'] ?? '', $kamus['dosen']);
            if (($b['nidn_ketua'] ?? '') === '') {
                $galat[] = 'NIDN ketua kosong.';
            } elseif ($ketua === null) {
                $galat[] = "Ketua dengan NIDN/NUPTK {$b['nidn_ketua']} tidak ada di Data Dosen.";
            }
            $anggota = [];
            foreach (array_filter(array_map('trim', preg_split('/[,;\n]/', $b['nidn_anggota'] ?? ''))) as $nidn) {
                $d = $this->dosen($nidn, $kamus['dosen']);
                if ($d === null) {
                    $galat[] = "Anggota dengan NIDN/NUPTK {$nidn} tidak ada di Data Dosen.";
                } elseif ($d->is($ketua) || isset($anggota[$d->id])) {
                    $galat[] = "Dosen {$d->nama} tercantum lebih dari sekali.";
                } else {
                    $anggota[$d->id] = $d;
                }
            }
            $r['ketua'] = $ketua?->id;
            $r['anggota'] = array_keys($anggota);

            $r['mahasiswa'] = [];
            foreach (array_filter(array_map('trim', explode(';', $b['mahasiswa'] ?? ''))) as $m) {
                preg_match('/^(.*?)\s*(?:\(([^)]*)\))?$/', $m, $cocok);
                $r['mahasiswa'][] = ['nama' => mb_substr($cocok[1] ?: $m, 0, 255), 'nim' => filled($cocok[2] ?? null) ? mb_substr($cocok[2], 0, 255) : null];
            }

            $r['mitra'] = array_values(array_unique(array_filter(array_map('trim', explode(';', $b['mitra'] ?? '')))));
            if ($this->pkm() && $r['mitra'] === []) {
                $galat[] = 'Kegiatan PkM wajib mencantumkan minimal satu mitra.';
            }
            $baru = array_filter($r['mitra'], fn ($nama) => ! $kamus['mitra']->has(mb_strtolower($nama)));
            if ($baru) {
                $peringatan[] = 'Mitra baru masuk data master: '.implode(', ', $baru).'.';
            }
            if (collect($r['mitra'])->contains(fn ($nama) => mb_strlen($nama) > 255)) {
                $galat[] = 'Nama mitra paling panjang 255 karakter.';
            }

            $r['kabupaten_kota_kode'] = null;
            if (($b['kabupaten_kota'] ?? '') !== '') {
                $kab = $this->kabupaten($b['kabupaten_kota'], $kamus['kabupaten']);
                if ($kab === null) {
                    $galat[] = "Kabupaten/kota \"{$b['kabupaten_kota']}\" tidak dikenal atau namanya ada lebih dari satu; tulis kodenya.";
                } else {
                    $r['kabupaten_kota_kode'] = $kab->kode;
                }
            }

            if ($this->pkm()) {
                $r['jumlah_sasaran'] = null;
                if (($b['jumlah_sasaran'] ?? '') !== '') {
                    if (! ctype_digit($b['jumlah_sasaran'])) {
                        $galat[] = 'Jumlah sasaran harus berupa angka.';
                    } else {
                        $r['jumlah_sasaran'] = (int) $b['jumlah_sasaran'];
                    }
                }
                $tautan = array_filter([$b['tautan_video'] ?? '', ...preg_split('/[\s;]+/', $b['tautan_media'] ?? '')]);
                if (collect($tautan)->contains(fn ($t) => ! filter_var($t, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $t))) {
                    $galat[] = 'Tautan video dan media harus berupa alamat web (https://...).';
                }
            }

            if ($r['status_kegiatan'] === RiwayatKegiatan::SELESAI) {
                $peringatan[] = 'Laporan akhir belum diunggah; lengkapi lewat form setelah diimpor.';
            }

            // The same activity entered twice, in this file or already recorded.
            $ganda = false;
            if ($judul !== '' && $r['tahun_mulai']) {
                $kunci = AlurRiwayat::normalkan($judul).'|'.$r['tahun_mulai'];
                if (isset($diFile[$kunci])) {
                    $peringatan[] = "Sama dengan baris {$diFile[$kunci]} di berkas ini.";
                    $ganda = true;
                }
                $diFile[$kunci] ??= $nomor;
                $mirip = app(AlurRiwayat::class)->cariGanda($this->jenis, $judul, $r['tahun_mulai']);
                if ($mirip->isNotEmpty()) {
                    $peringatan[] = 'Mirip data yang sudah ada: '.$mirip->map(fn ($m) => "\"{$m['judul']}\" ({$m['keterangan']})")->join('; ').'.';
                    $ganda = true;
                }
            }

            $b['_simpan'] = $r;
            $b['_ringkas'] = [
                'ketua' => $ketua?->namaLengkap(),
                'anggota' => collect($anggota)->map(fn (Dosen $d) => $d->namaLengkap())->join(', '),
            ];

            return ['data' => $b, 'aksi' => $ganda ? 'ganda' : 'baru', 'galat' => array_values(array_unique($galat)), 'peringatan' => $peringatan];
        }, $baris);
    }

    /**
     * Saves the checked rows; rows that look like duplicates only when asked.
     */
    public function simpan(array $hasil, bool $sertakanGanda = false): int
    {
        $simpan = array_values(array_filter($hasil, fn ($h) => ! $h['galat'] && ($sertakanGanda || $h['aksi'] !== 'ganda')));

        return DB::transaction(function () use ($simpan) {
            $dosen = Dosen::whereIn('id', collect($simpan)->flatMap(fn ($h) => [$h['data']['_simpan']['ketua'], ...$h['data']['_simpan']['anggota']]))->get()->keyBy('id');
            $mitra = [];

            foreach ($simpan as $h) {
                $b = $h['data'];
                $r = $b['_simpan'];
                $riwayat = new RiwayatKegiatan([
                    'jenis_kegiatan' => $this->jenis,
                    'judul' => $b['judul'],
                    'tahun_mulai' => $r['tahun_mulai'],
                    'tahun_selesai' => $r['tahun_selesai'],
                    'status_kegiatan' => $r['status_kegiatan'],
                    'sumber_dana' => $r['sumber_dana'],
                    'pemberi_dana' => $b['pemberi_dana'] ?: null,
                    'skema' => $b['skema'] ?: null,
                    'nilai_dana' => $r['nilai_dana'],
                    'no_kontrak' => $b['no_kontrak'] ?: null,
                    'kabupaten_kota_kode' => $r['kabupaten_kota_kode'],
                    'alamat_lokasi' => $b['alamat_lokasi'] ?: null,
                    'ringkasan' => $b['ringkasan'] ?: null,
                    ...($this->pkm() ? [
                        'jumlah_sasaran' => $r['jumlah_sasaran'],
                        'catatan_dampak' => $b['catatan_dampak'] ?: null,
                        'tautan_video' => $b['tautan_video'] ?: null,
                        'tautan_media' => implode("\n", array_filter(preg_split('/[\s;]+/', $b['tautan_media'] ?? ''))) ?: null,
                    ] : []),
                ]);
                $riwayat->forceFill([
                    'diinput_oleh' => $this->oleh->id,
                    'status_verifikasi' => RiwayatKegiatan::VALID,
                    'diverifikasi_lppm_oleh' => $this->oleh->id,
                    'diverifikasi_lppm_at' => now(),
                ])->save();

                $urutan = 0;
                foreach ([$r['ketua'], ...$r['anggota']] as $i => $id) {
                    $riwayat->anggota()->create(['tipe' => TipeAnggota::Dosen, 'dosen_id' => $id, 'nama' => $dosen[$id]->namaLengkap(),
                        'peran' => $i === 0 ? RiwayatKegiatan::KETUA : RiwayatKegiatan::ANGGOTA, 'urutan' => ++$urutan]);
                }
                foreach ($r['mahasiswa'] as $m) {
                    $riwayat->anggota()->create(['tipe' => TipeAnggota::Mahasiswa, 'nama' => $m['nama'], 'keterangan' => $m['nim'],
                        'peran' => RiwayatKegiatan::ANGGOTA, 'urutan' => ++$urutan]);
                }
                $riwayat->mitra()->sync(collect($r['mitra'])->map(fn ($nama) => $mitra[mb_strtolower($nama)]
                    ??= Mitra::usulkan(['nama' => $nama, 'is_active' => true], $this->oleh, true)->id)->unique()->all());
            }

            return count($simpan);
        });
    }

    /**
     * The template: the Riwayat sheet to fill in, and locked sheets with the
     * instructions, the codes and the regencies and cities.
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
        $teks = (new Style)->setFormat('@');

        $sheet = $writer->getCurrentSheet()->setName('Riwayat');
        foreach ($this->kolom() as $i => $kolom) {
            $sheet->setColumnWidth(in_array($kolom, ['judul', 'ringkasan', 'mitra', 'mahasiswa', 'catatan_dampak'], true) ? 40 : 18, $i + 1);
        }
        $writer->addRow(Row::fromValues($this->kolom(), $tebal));
        foreach ($this->contoh() as $baris) {
            // Text cells, so a NIDN keeps its leading zero.
            $writer->addRow(Row::fromValues($baris, $teks));
        }

        $terkunci = new SheetProtection(password: $kunci, lockSheet: true, lockObjects: true, lockScenarios: true);
        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk')->setSheetProtection($terkunci);
        $sheet->setColumnWidth(120, 1);
        $writer->addRow(Row::fromValues(['Cara mengisi'], $tebal));
        foreach ([
            '1. Isi sheet Riwayat mulai baris 2, satu baris per kegiatan. Jangan mengubah judul kolom di baris 1. Hapus baris contoh sebelum mengunggah.',
            '2. Wajib diisi: judul, tahun_mulai, status_kegiatan, sumber_dana, nidn_ketua'.($this->pkm() ? ', dan mitra.' : '.'),
            '3. status_kegiatan: selesai atau berjalan. sumber_dana: kode dari sheet Daftar kode. Hibah LPPM tidak perlu diimpor karena sudah tercatat dari proposal.',
            '4. nidn_ketua dan nidn_anggota: NIDN atau NUPTK dosen yang sudah ada di Data Dosen. Anggota dipisah koma. Format sel sebagai teks agar angka 0 di depan tidak hilang.',
            '5. mahasiswa: nama dan NIM dalam kurung, dipisah titik koma. Contoh: Ayu Lestari (2201001); Bagus Pratama (2201002).',
            '6. mitra: nama mitra, dipisah titik koma. Mitra yang belum ada langsung masuk data master.',
            '7. kabupaten_kota: kode atau nama lengkap dari sheet Kabupaten-kota, misalnya 3404 atau Kabupaten Sleman. Kecamatan dan desa ditulis di alamat_lokasi.',
            '8. nilai_dana: angka rupiah tanpa titik, misalnya 15000000.',
            $this->pkm() ? '9. tautan_video: satu tautan YouTube. tautan_media: tautan berita media massa, dipisah spasi.' : '9. Luaran (publikasi, HKI, paten) ditautkan lewat form setelah diimpor.',
            '10. Berkas laporan akhir tidak ikut diimpor. Kegiatan berstatus selesai ditandai "laporan belum diunggah" dan dilengkapi lewat form.',
            '11. Data hasil impor langsung tervalidasi. Baris yang mirip data lain ditampilkan saat pratinjau dan dilewati kecuali Anda memilih tetap menyimpannya.',
        ] as $petunjuk) {
            $writer->addRow(Row::fromValues([$petunjuk]));
        }

        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('Daftar kode')->setSheetProtection($terkunci);
        $sheet->setColumnWidth(22, 1);
        $sheet->setColumnWidth(22, 2);
        $sheet->setColumnWidth(40, 3);
        $writer->addRow(Row::fromValues(['kolom', 'kode', 'keterangan'], $tebal));
        foreach (RiwayatKegiatan::STATUS_KEGIATAN as $kode => $label) {
            $writer->addRow(Row::fromValues(['status_kegiatan', $kode, $label]));
        }
        foreach (RiwayatKegiatan::SUMBER_DANA as $kode => $label) {
            $writer->addRow(Row::fromValues(['sumber_dana', $kode, $label]));
        }

        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('Kabupaten-kota')->setSheetProtection($terkunci);
        $sheet->setColumnWidth(10, 1);
        $sheet->setColumnWidth(35, 2);
        $sheet->setColumnWidth(35, 3);
        $writer->addRow(Row::fromValues(['kode', 'kabupaten/kota', 'provinsi'], $tebal));
        foreach (KabupatenKota::with('provinsi')->orderBy('kode')->get() as $k) {
            $writer->addRow(Row::fromValues([$k->kode, $k->nama, $k->provinsi->nama], $teks));
        }

        $writer->setCurrentSheet($writer->getSheets()[0]);
        $writer->close();

        return response()->download($berkas, "templat-impor-{$jenis}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function pkm(): bool
    {
        return $this->jenis === JenisKegiatan::Pkm;
    }

    /**
     * Lecturers by NIDN and NUPTK, partners the importer may pick by
     * lower-cased name, and the regencies and cities.
     *
     * @return array{dosen: Collection<string, Dosen>, mitra: Collection<string, int>, kabupaten: Collection<int, KabupatenKota>}
     */
    private function kamus(): array
    {
        $dosen = Dosen::all();

        return [
            'dosen' => $dosen->filter(fn (Dosen $d) => filled($d->nidn))->keyBy(fn (Dosen $d) => trim($d->nidn))
                ->union($dosen->filter(fn (Dosen $d) => filled($d->nuptk))->keyBy(fn (Dosen $d) => trim($d->nuptk))),
            'mitra' => Mitra::bisaDipilih($this->oleh)->get(['id', 'nama'])->mapWithKeys(fn (Mitra $m) => [mb_strtolower(trim($m->nama)) => $m->id]),
            'kabupaten' => KabupatenKota::all(['kode', 'nama']),
        ];
    }

    /**
     * A NIDN typed as a number loses its leading zeros; they are put back.
     *
     * @param  Collection<string, Dosen>  $dosen
     */
    private function dosen(string $nomor, Collection $dosen): ?Dosen
    {
        $nomor = preg_replace('/\s+/', '', $nomor);
        if ($nomor === '') {
            return null;
        }

        return $dosen->get($nomor) ?? (ctype_digit($nomor) ? $dosen->get(str_pad($nomor, 10, '0', STR_PAD_LEFT)) : null);
    }

    /**
     * By code, by full name, or by name without "Kabupaten"/"Kota" when that is unique.
     *
     * @param  Collection<int, KabupatenKota>  $semua
     */
    private function kabupaten(string $isi, Collection $semua): ?KabupatenKota
    {
        $isi = mb_strtolower(trim($isi));
        $cocok = $semua->filter(fn (KabupatenKota $k) => $k->kode === $isi || mb_strtolower($k->nama) === $isi);
        if ($cocok->isEmpty()) {
            $cocok = $semua->filter(fn (KabupatenKota $k) => mb_strtolower(preg_replace('/^(kabupaten|kota)\s+/i', '', $k->nama)) === $isi);
        }

        return $cocok->count() === 1 ? $cocok->first() : null;
    }

    private function tahun(string $isi): ?int
    {
        return ctype_digit($isi) && (int) $isi >= 1950 && (int) $isi <= now()->year + 1 ? (int) $isi : null;
    }

    /**
     * A code from its code or its label, case aside; a label like
     * "Kemdiktisaintek (DRTPM/BIMA)" also matches by the words before the bracket.
     *
     * @param  array<string, string>  $pilihan
     */
    private function kode(string $isi, array $pilihan): ?string
    {
        $isi = mb_strtolower(trim($isi));
        foreach ($pilihan as $kode => $label) {
            $label = mb_strtolower($label);
            if ($isi !== '' && ($isi === $kode || $isi === $label || $isi === trim(explode('(', $label)[0]))) {
                return $kode;
            }
        }

        return null;
    }
}
