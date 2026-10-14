<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Berkas;
use App\Models\Laporan;
use App\Models\Logbook;
use App\Models\Luaran;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * A funded grant at work: the team keeps a logbook, the lead sends the
 * progress report that puts the proposal under monev, reports what became of
 * each output, and sends the final report that the operator verifies.
 */
class AlurPelaksanaan
{
    public function __construct(private AlurProposal $alur, private Pemberitahu $pemberitahu) {}

    /**
     * The lead and the lecturers who confirmed they are on the team.
     */
    public function anggotaTim(Proposal $proposal, User $user): bool
    {
        $dosen = $user->dosen;
        if ($dosen === null) {
            return false;
        }

        return $proposal->ketua_id === $dosen->id
            || $proposal->anggota()->where('tipe', TipeAnggota::Dosen)->where('dosen_id', $dosen->id)
                ->whereNotNull('dikonfirmasi_at')->whereNull('ditolak_at')->exists();
    }

    public function bisaMencatatLogbook(Proposal $proposal): bool
    {
        return in_array($proposal->status, [StatusProposal::Berjalan, StatusProposal::Dimonev], true);
    }

    /**
     * @param  array{tanggal: string, kegiatan: string, persentase_progres?: int|string|null}  $data
     */
    public function catatLogbook(Proposal $proposal, array $data, ?UploadedFile $bukti, User $oleh): Logbook
    {
        $this->pastikan($this->anggotaTim($proposal, $oleh), 'Hanya ketua dan anggota dosen yang bisa mengisi logbook.');
        $this->pastikan($this->bisaMencatatLogbook($proposal), 'Logbook diisi selama kegiatan berjalan.');
        $tanggal = Carbon::parse($data['tanggal']);
        $this->pastikan(! $tanggal->isFuture(), 'Tanggal kegiatan tidak boleh di masa depan.');
        $this->pastikan($proposal->tgl_mulai === null || $tanggal->gte($proposal->tgl_mulai), 'Tanggal kegiatan sebelum kontrak dimulai.');

        return DB::transaction(function () use ($proposal, $data, $tanggal, $bukti, $oleh) {
            $logbook = $proposal->logbook()->create([
                'tanggal' => $tanggal,
                'kegiatan' => $data['kegiatan'],
                'persentase_progres' => filled($data['persentase_progres'] ?? null) ? (int) $data['persentase_progres'] : null,
                'dicatat_oleh' => $oleh->dosen->id,
            ]);
            if ($bukti) {
                $this->simpanBerkas($logbook, Berkas::BUKTI_LOGBOOK, $bukti, "logbook/{$proposal->id}", $oleh);
            }

            return $logbook;
        });
    }

    /**
     * Whoever wrote an entry can remove it while the logbook is still open.
     */
    public function hapusLogbook(Logbook $logbook, User $oleh): void
    {
        $this->pastikan($logbook->dicatat_oleh !== null && $logbook->dicatat_oleh === $oleh->dosen?->id, 'Hanya pencatat yang bisa menghapus entri logbook.');
        $this->pastikan($this->bisaMencatatLogbook($logbook->proposal), 'Logbook sudah ditutup.');

        DB::transaction(function () use ($logbook) {
            foreach ($logbook->berkas as $berkas) {
                Storage::delete($berkas->path);
                $berkas->forceDelete();
            }
            $logbook->delete();
        });
    }

    /**
     * The lead sends the progress report with the first tranche's spending
     * report; the proposal moves to monev and LPPM is told.
     *
     * @param  array{ringkasan: string, persentase_capaian: int|string, persentase_anggaran_terpakai: int|string}  $data
     */
    public function kirimLaporanKemajuan(Proposal $proposal, array $data, UploadedFile $laporan, UploadedFile $keuangan, User $oleh): Laporan
    {
        $this->pastikan($proposal->isKetua($oleh->dosen), 'Laporan kemajuan dikirim oleh ketua.');
        $this->pastikan($proposal->status === StatusProposal::Berjalan, 'Laporan kemajuan dikirim saat kegiatan berjalan.');
        $this->pastikan($proposal->logbook()->exists(), 'Isi logbook kegiatan terlebih dahulu.');

        $hasil = DB::transaction(function () use ($proposal, $data, $laporan, $keuangan, $oleh) {
            $hasil = $proposal->laporan()->create([
                'jenis' => Laporan::KEMAJUAN,
                'ringkasan' => $data['ringkasan'],
                'persentase_capaian' => (int) $data['persentase_capaian'],
                'persentase_anggaran_terpakai' => (int) $data['persentase_anggaran_terpakai'],
                'status' => Laporan::DIAJUKAN,
                'diajukan_at' => now(),
            ]);
            $this->simpanBerkas($hasil, Berkas::LAPORAN_KEMAJUAN, $laporan, "laporan/{$proposal->id}", $oleh);
            $this->simpanBerkas($hasil, Berkas::LAPORAN_KEUANGAN, $keuangan, "laporan/{$proposal->id}", $oleh);
            $this->alur->lanjutkan($proposal, StatusProposal::Dimonev, $oleh, 'Laporan kemajuan dikirim.');

            return $hasil;
        });

        $this->pemberitahu->kePeran([Role::ADMIN_LPPM], 'Laporan kemajuan masuk',
            "Ketua proposal \"{$proposal->judul}\" mengirim laporan kemajuan. Tugaskan reviewer monev.", route('admin.monev.index'));

        return $hasil;
    }

    /**
     * The sent monev review of the proposal, if any.
     */
    public function hasilMonev(Proposal $proposal): ?Penilaian
    {
        return $proposal->penilaian()->where('tahap', Penilaian::MONEV)->where('status', Penilaian::SELESAI)->latest('dinilai_at')->first();
    }

    /**
     * The newest final report, whatever its state.
     */
    public function laporanAkhir(Proposal $proposal): ?Laporan
    {
        return $proposal->laporan()->where('jenis', Laporan::AKHIR)->latest('diajukan_at')->latest('id')->first();
    }

    /**
     * The lead reports how far an output has come; the operator then validates
     * it again.
     */
    public function laporCapaianLuaran(Luaran $luaran, string $capaian, ?string $urlBukti, User $oleh, ?int $karyaId = null): void
    {
        $proposal = $luaran->proposal;
        $this->pastikan($proposal->isKetua($oleh->dosen), 'Capaian luaran dilaporkan oleh ketua.');
        $this->pastikan($this->bisaMencatatLogbook($proposal), 'Capaian luaran dilaporkan selama kegiatan berjalan.');
        $this->pastikan(array_key_exists($capaian, Luaran::CAPAIAN), 'Pilihan capaian tidak dikenal.');

        // An output can point at one of the lead's own publications, HKI or patents.
        $tautan = ['publikasi_id' => null, 'hki_id' => null, 'paten_id' => null];
        if ($karyaId !== null) {
            $model = $luaran->jenis->modelKarya();
            $this->pastikan($model !== null, 'Luaran jenis ini tidak bisa ditautkan ke publikasi, HKI, atau paten.');
            $karya = $model::milikDosen($oleh->dosen)->find($karyaId);
            $this->pastikan($karya !== null, 'Pilih data yang mencantumkan nama Anda.');
            $tautan[$karya->getForeignKey()] = $karya->id;
        }
        $this->pastikan($capaian === Luaran::DIRENCANAKAN || filled($urlBukti) || $karyaId !== null, 'Isi tautan bukti capaian luaran.');

        $luaran->fill(['status_capaian' => $capaian, 'url_bukti' => $urlBukti, ...$tautan])
            ->forceFill(['status_validasi' => Luaran::MENUNGGU, 'divalidasi_oleh' => null, 'divalidasi_at' => null, 'catatan_validasi' => null])
            ->save();
    }

    /**
     * The operator accepts or rejects what the lead reported for an output.
     */
    public function validasiLuaran(Luaran $luaran, bool $valid, ?string $catatan, User $oleh): void
    {
        $this->pastikan($luaran->status_capaian !== Luaran::DIRENCANAKAN, 'Ketua belum melaporkan capaian luaran ini.');
        $this->pastikan($valid || filled($catatan), 'Tulis alasan luaran ditolak.');

        $luaran->validasi($oleh, $valid, $catatan);

        if (! $valid) {
            $this->pemberitahu->keKetua($luaran->proposal, 'Capaian luaran ditolak',
                "Capaian luaran {$luaran->jenis->label()} untuk proposal \"{$luaran->proposal->judul}\" ditolak. Alasan: {$catatan}");
        }
    }

    /**
     * After the monev result is out, the lead sends the final report with the
     * full spending report. A returned report is replaced by a new one.
     *
     * @param  array{ringkasan: string, persentase_capaian: int|string}  $data
     */
    public function kirimLaporanAkhir(Proposal $proposal, array $data, UploadedFile $laporan, UploadedFile $keuangan, User $oleh): Laporan
    {
        $this->pastikan($proposal->isKetua($oleh->dosen), 'Laporan akhir dikirim oleh ketua.');
        $this->pastikan($proposal->status === StatusProposal::Dimonev, 'Laporan akhir dikirim setelah laporan kemajuan.');
        $this->pastikan($this->hasilMonev($proposal) !== null, 'Laporan akhir dikirim setelah hasil monev keluar.');
        $terakhir = $this->laporanAkhir($proposal);
        $this->pastikan($terakhir === null || $terakhir->status === Laporan::DIKEMBALIKAN, 'Laporan akhir sudah dikirim.');

        $hasil = DB::transaction(function () use ($proposal, $data, $laporan, $keuangan, $oleh) {
            $hasil = $proposal->laporan()->create([
                'jenis' => Laporan::AKHIR,
                'ringkasan' => $data['ringkasan'],
                'persentase_capaian' => (int) $data['persentase_capaian'],
                'persentase_anggaran_terpakai' => 100,
                'status' => Laporan::DIAJUKAN,
                'diajukan_at' => now(),
            ]);
            $this->simpanBerkas($hasil, Berkas::LAPORAN_AKHIR, $laporan, "laporan/{$proposal->id}", $oleh);
            $this->simpanBerkas($hasil, Berkas::LAPORAN_KEUANGAN, $keuangan, "laporan/{$proposal->id}", $oleh);

            return $hasil;
        });

        $this->pemberitahu->kePeran([Role::OPERATOR, Role::ADMIN_LPPM], 'Laporan akhir masuk',
            "Ketua proposal \"{$proposal->judul}\" mengirim laporan akhir. Periksa dan verifikasi.", route('verifikasi.index'));

        return $hasil;
    }

    /**
     * The operator accepts the final report, or sends it back with a reason.
     */
    public function verifikasiLaporanAkhir(Laporan $laporan, bool $terima, ?string $catatan, User $oleh): void
    {
        $this->pastikan($laporan->jenis === Laporan::AKHIR && $laporan->status === Laporan::DIAJUKAN, 'Laporan ini tidak sedang menunggu verifikasi.');
        $this->pastikan($terima || filled($catatan), 'Tulis apa yang harus diperbaiki.');

        $laporan->fill([
            'status' => $terima ? Laporan::DIVERIFIKASI : Laporan::DIKEMBALIKAN,
            'diverifikasi_oleh' => $oleh->id,
            'diverifikasi_at' => now(),
            'catatan_verifikasi' => $catatan,
        ])->save();

        $proposal = $laporan->proposal;
        if ($terima) {
            $this->pemberitahu->keKetua($proposal, 'Laporan akhir diverifikasi',
                "Laporan akhir proposal \"{$proposal->judul}\" sudah diverifikasi. Termin 2 dicairkan setelah luaran wajib tervalidasi.");
            $this->pemberitahu->kePeran([Role::KEUANGAN], 'Laporan akhir diverifikasi',
                "Laporan akhir proposal \"{$proposal->judul}\" sudah diverifikasi.", route('pencairan.index'));
        } else {
            $this->pemberitahu->keKetua($proposal, 'Laporan akhir dikembalikan',
                "Laporan akhir proposal \"{$proposal->judul}\" dikembalikan untuk diperbaiki. Catatan: {$catatan}");
        }
    }

    /**
     * Remind leads 14, 7 and 1 days before a report is due. Returns how many.
     */
    public function ingatkanTenggat(): int
    {
        $tanggal = collect([14, 7, 1])->map(fn ($n) => today()->addDays($n)->toDateString())->all();
        $jumlah = 0;

        $kemajuan = Proposal::where('status', StatusProposal::Berjalan)
            ->whereHas('periode', fn ($q) => $q->whereIn(DB::raw('date(tgl_laporan_kemajuan)'), $tanggal))
            ->with('periode', 'ketua.user')->get();
        foreach ($kemajuan as $proposal) {
            $this->pemberitahu->keKetua($proposal, 'Pengingat laporan kemajuan',
                "Laporan kemajuan proposal \"{$proposal->judul}\" ditunggu paling lambat {$proposal->periode->tgl_laporan_kemajuan->translatedFormat('j F Y')}.");
            $jumlah++;
        }

        $akhir = Proposal::where('status', StatusProposal::Dimonev)
            ->whereHas('periode', fn ($q) => $q->whereIn(DB::raw('date(tgl_laporan_akhir)'), $tanggal))
            ->whereDoesntHave('laporan', fn ($q) => $q->where('jenis', Laporan::AKHIR)->whereIn('status', [Laporan::DIAJUKAN, Laporan::DIVERIFIKASI]))
            ->with('periode', 'ketua.user')->get();
        foreach ($akhir as $proposal) {
            $this->pemberitahu->keKetua($proposal, 'Pengingat laporan akhir',
                "Laporan akhir proposal \"{$proposal->judul}\" ditunggu paling lambat {$proposal->periode->tgl_laporan_akhir->translatedFormat('j F Y')}.");
            $jumlah++;
        }

        return $jumlah;
    }

    private function simpanBerkas(Logbook|Laporan $pemilik, string $kategori, UploadedFile $file, string $folder, User $oleh): void
    {
        $pemilik->berkas()->create([
            'kategori' => $kategori,
            'nama_asli' => $file->getClientOriginalName(),
            'path' => $file->store($folder),
            'mime' => $file->getMimeType(),
            'ukuran_byte' => $file->getSize(),
            'diunggah_oleh' => $oleh->id,
        ]);
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['pelaksanaan' => $pesan]);
        }
    }
}
