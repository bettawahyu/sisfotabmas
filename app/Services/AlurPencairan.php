<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Models\Berkas;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * From approval to money: LPPM records the contract (Didanai), then finance
 * records the first tranche with its proof (Berjalan).
 */
class AlurPencairan
{
    public function __construct(private AlurProposal $alur, private Pemberitahu $pemberitahu) {}

    /**
     * @param  array{no_kontrak: string, tgl_kontrak: string, tgl_mulai: string, tgl_selesai: string}  $data
     */
    public function catatKontrak(Proposal $proposal, array $data, User $oleh): void
    {
        $this->pastikan($proposal->status === StatusProposal::Disetujui, 'Kontrak hanya dicatat untuk proposal berstatus Disetujui.');
        $this->pastikan($proposal->no_sk !== null, 'Catat SK pendanaan terlebih dahulu.');

        DB::transaction(function () use ($proposal, $data, $oleh) {
            $proposal->forceFill([
                'no_kontrak' => $data['no_kontrak'],
                'tgl_kontrak' => $data['tgl_kontrak'],
                'tgl_mulai' => $data['tgl_mulai'],
                'tgl_selesai' => $data['tgl_selesai'],
            ]);
            $this->alur->lanjutkan($proposal, StatusProposal::Didanai, $oleh, 'Kontrak '.$data['no_kontrak'].' dicatat.');
        });

        $this->pemberitahu->keKetua($proposal, 'Kontrak penelitian dicatat',
            "Kontrak nomor {$data['no_kontrak']} untuk proposal \"{$proposal->judul}\" sudah dicatat. Termin 1 menunggu pencairan oleh bagian keuangan.");
        $this->pemberitahu->kePeran([Role::KEUANGAN], 'Termin 1 siap dicairkan',
            "Kontrak proposal \"{$proposal->judul}\" sudah dicatat dan termin 1 siap dicairkan.", route('pencairan.index'));
    }

    /**
     * The amount of a tranche: the call's first-tranche share of the approved
     * fund, and the remainder for the second, so the two always add up.
     */
    public function jumlahTermin(Proposal $proposal, int $termin): int
    {
        $pertama = intdiv($proposal->dana_disetujui * $proposal->periode->termin_pertama_persen, 100);

        return $termin === 1 ? $pertama : $proposal->dana_disetujui - $pertama;
    }

    public function cairkanTermin1(Proposal $proposal, Carbon $tanggal, bool $pajakLunas, UploadedFile $bukti, User $oleh): PencairanDana
    {
        $this->pastikan($proposal->status === StatusProposal::Didanai, 'Termin 1 dicairkan setelah kontrak dicatat.');
        $this->pastikan($proposal->pencairan()->where('termin_ke', 1)->doesntExist(), 'Termin 1 sudah dicatat.');
        $this->pastikan(! $tanggal->isFuture(), 'Tanggal cair tidak boleh di masa depan.');

        $pencairan = DB::transaction(function () use ($proposal, $tanggal, $pajakLunas, $bukti, $oleh) {
            $pencairan = $proposal->pencairan()->create([
                'termin_ke' => 1,
                'persentase' => $proposal->periode->termin_pertama_persen,
                'jumlah' => $this->jumlahTermin($proposal, 1),
                'tgl_cair' => $tanggal,
                'syarat' => PencairanDana::SYARAT_KONTRAK,
                'pajak_lunas' => $pajakLunas,
                'dicairkan_oleh' => $oleh->id,
            ]);
            $pencairan->berkas()->create([
                'kategori' => Berkas::BUKTI_PENCAIRAN,
                'nama_asli' => $bukti->getClientOriginalName(),
                'path' => $bukti->store("pencairan/{$proposal->id}"),
                'mime' => $bukti->getMimeType(),
                'ukuran_byte' => $bukti->getSize(),
                'diunggah_oleh' => $oleh->id,
            ]);
            $this->alur->lanjutkan($proposal, StatusProposal::Berjalan, $oleh, 'Termin 1 dicairkan.');

            return $pencairan;
        });

        $this->pemberitahu->keKetua($proposal, 'Termin 1 dicairkan',
            'Dana termin 1 sebesar Rp '.number_format($pencairan->jumlah, 0, ',', '.')." untuk proposal \"{$proposal->judul}\" sudah dicairkan. Kegiatan bisa dimulai.");

        return $pencairan;
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['pencairan' => $pesan]);
        }
    }
}
