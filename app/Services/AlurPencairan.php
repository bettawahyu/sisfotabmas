<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Models\Berkas;
use App\Models\Laporan;
use App\Models\PencairanDana;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * From approval to money: LPPM records the contract (Didanai), finance records
 * the first tranche with its proof (Berjalan), and the second once the final
 * report and the required outputs are in (Selesai).
 */
class AlurPencairan
{
    public function __construct(private AlurProposal $alur, private AlurPelaksanaan $pelaksanaan, private Pemberitahu $pemberitahu) {}

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
            $this->simpanBukti($pencairan, $bukti, $oleh);
            $this->alur->lanjutkan($proposal, StatusProposal::Berjalan, $oleh, 'Termin 1 dicairkan.');

            return $pencairan;
        });

        $this->pemberitahu->keKetua($proposal, 'Termin 1 dicairkan',
            'Dana termin 1 sebesar Rp '.number_format($pencairan->jumlah, 0, ',', '.')." untuk proposal \"{$proposal->judul}\" sudah dicairkan. Kegiatan bisa dimulai.");

        return $pencairan;
    }

    /**
     * What still holds the second tranche back, as messages. Empty means it can be paid.
     *
     * @return list<string>
     */
    public function syaratTermin2(Proposal $proposal): array
    {
        $masalah = [];
        if ($proposal->status !== StatusProposal::Dimonev) {
            return ['Termin 2 dicairkan untuk proposal dalam tahap monev.'];
        }

        $monev = $this->pelaksanaan->hasilMonev($proposal);
        if ($monev === null) {
            $masalah[] = 'Hasil monev belum keluar.';
        } elseif ($monev->rekomendasi === Penilaian::MONEV_BERMASALAH) {
            $masalah[] = 'Hasil monev Bermasalah; termin 2 ditahan sampai LPPM meninjau ulang hasil monev.';
        }

        if ($this->pelaksanaan->laporanAkhir($proposal)?->status !== Laporan::DIVERIFIKASI) {
            $masalah[] = 'Laporan akhir belum diverifikasi.';
        }

        $belum = $proposal->luaran()->where('kategori', 'wajib')->get()->reject->memenuhiTermin2();
        if ($belum->isNotEmpty()) {
            $masalah[] = 'Luaran wajib belum tervalidasi minimal submitted: '.$belum->map(fn ($l) => $l->jenis->label())->join(', ').'.';
        }

        return $masalah;
    }

    public function cairkanTermin2(Proposal $proposal, Carbon $tanggal, bool $pajakLunas, UploadedFile $bukti, User $oleh): PencairanDana
    {
        $masalah = $this->syaratTermin2($proposal);
        if ($masalah !== []) {
            throw ValidationException::withMessages(['pencairan' => $masalah]);
        }
        $this->pastikan($proposal->pencairan()->where('termin_ke', 1)->exists(), 'Termin 1 belum dicatat.');
        $this->pastikan($proposal->pencairan()->where('termin_ke', 2)->doesntExist(), 'Termin 2 sudah dicatat.');
        $this->pastikan(! $tanggal->isFuture(), 'Tanggal cair tidak boleh di masa depan.');

        $pencairan = DB::transaction(function () use ($proposal, $tanggal, $pajakLunas, $bukti, $oleh) {
            $pencairan = $proposal->pencairan()->create([
                'termin_ke' => 2,
                'persentase' => 100 - $proposal->periode->termin_pertama_persen,
                'jumlah' => $this->jumlahTermin($proposal, 2),
                'tgl_cair' => $tanggal,
                'syarat' => PencairanDana::SYARAT_LAPORAN_AKHIR,
                'pajak_lunas' => $pajakLunas,
                'dicairkan_oleh' => $oleh->id,
            ]);
            $this->simpanBukti($pencairan, $bukti, $oleh);
            $this->alur->lanjutkan($proposal, StatusProposal::Selesai, $oleh, 'Termin 2 dicairkan.');

            return $pencairan;
        });

        $this->pemberitahu->keKetua($proposal, 'Termin 2 dicairkan',
            'Dana termin 2 sebesar Rp '.number_format($pencairan->jumlah, 0, ',', '.')." untuk proposal \"{$proposal->judul}\" sudah dicairkan. Kegiatan dinyatakan selesai.");

        return $pencairan;
    }

    private function simpanBukti(PencairanDana $pencairan, UploadedFile $bukti, User $oleh): void
    {
        $pencairan->berkas()->create([
            'kategori' => Berkas::BUKTI_PENCAIRAN,
            'nama_asli' => $bukti->getClientOriginalName(),
            'path' => $bukti->store("pencairan/{$pencairan->proposal_id}"),
            'mime' => $bukti->getMimeType(),
            'ukuran_byte' => $bukti->getSize(),
            'diunggah_oleh' => $oleh->id,
        ]);
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['pencairan' => $pesan]);
        }
    }
}
