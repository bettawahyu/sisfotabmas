<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Berkas;
use App\Models\Laporan;
use App\Models\Logbook;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * A funded grant at work: the team keeps a logbook, and the lead sends the
 * progress report that puts the proposal under monev.
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
