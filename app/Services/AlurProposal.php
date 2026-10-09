<?php

namespace App\Services;

use App\Enums\JenisKegiatan;
use App\Enums\KategoriRab;
use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves proposals through the submission flow: the lead sends, the faculty
 * head endorses, LPPM screens. Every status change is checked against
 * StatusProposal::bolehMenjadi() and written to proposal_status_log.
 */
class AlurProposal
{
    /**
     * Everything that still stops the lead from sending the proposal, as messages
     * for the lead. An empty list means it can be sent.
     *
     * @return list<string>
     */
    public function kekurangan(Proposal $proposal): array
    {
        $proposal->loadMissing(['periode.skema', 'ketua', 'anggota', 'rab', 'luaran', 'mitra', 'berkasSubstansi']);
        $periode = $proposal->periode;
        $masalah = [];

        if ($proposal->status === StatusProposal::Dikembalikan) {
            if ($proposal->batas_perbaikan_at?->isPast()) {
                $masalah[] = 'Batas waktu perbaikan sudah lewat.';
            }
        } elseif (! $periode->menerimaPengajuan()) {
            $masalah[] = 'Periode hibah ini tidak sedang menerima pengajuan.';
        }

        if (! $proposal->ketua->identitasLengkap()) {
            $masalah[] = 'Lengkapi NIDN atau NUPTK dan ID SINTA ketua di profil dosen.';
        }

        if ($proposal->berkasSubstansi === null) {
            $masalah[] = 'Unggah substansi proposal (PDF dari templat LPPM).';
        }

        $anggotaDosen = $proposal->anggota->where('tipe', TipeAnggota::Dosen);
        if ($anggotaDosen->whereNotNull('ditolak_at')->isNotEmpty()) {
            $masalah[] = 'Hapus anggota yang menolak undangan.';
        }
        if ($anggotaDosen->whereNull('ditolak_at')->whereNull('dikonfirmasi_at')->isNotEmpty()) {
            $masalah[] = 'Semua anggota dosen harus mengonfirmasi kesediaan.';
        }

        $total = $proposal->totalRab();
        if ($total <= 0) {
            $masalah[] = 'Isi rencana anggaran belanja (RAB).';
        } elseif ($total > $periode->dana_maksimal) {
            $masalah[] = 'Total RAB Rp '.number_format($total, 0, ',', '.').' melebihi plafon Rp '.number_format($periode->dana_maksimal, 0, ',', '.').'.';
        }

        $honor = (int) $proposal->rab->where('kategori', KategoriRab::Honorarium)->sum('total');
        if ($honor > 0 && ! $periode->honor_diizinkan) {
            $masalah[] = 'Periode ini tidak mengizinkan biaya honorarium.';
        } elseif ($honor > 0 && $periode->batas_honor_persen !== null && $honor * 100 > $total * $periode->batas_honor_persen) {
            $masalah[] = "Biaya honorarium melebihi {$periode->batas_honor_persen}% dari total RAB.";
        }

        if ($proposal->luaran->where('kategori', 'wajib')->isEmpty()) {
            $masalah[] = 'Tambahkan minimal satu luaran wajib.';
        }

        if ($periode->skema->jenis_kegiatan === JenisKegiatan::Pkm && $proposal->mitra->isEmpty()) {
            $masalah[] = 'Proposal PkM wajib mencantumkan minimal satu mitra.';
        }

        if ($proposal->status === StatusProposal::Draft) {
            array_push($masalah, ...$this->pelanggaranKuota($proposal));
        }

        return $masalah;
    }

    /**
     * The lead sends the proposal. A new proposal waits for the faculty head's
     * endorsement; a returned one goes straight back to LPPM.
     */
    public function kirim(Proposal $proposal, User $oleh): void
    {
        $masalah = $this->kekurangan($proposal);
        if ($masalah !== []) {
            throw ValidationException::withMessages(['proposal' => $masalah]);
        }

        DB::transaction(function () use ($proposal, $oleh) {
            $proposal->dana_diusulkan = $proposal->totalRab();
            $proposal->dikirim_at = now();

            if ($proposal->status === StatusProposal::Dikembalikan) {
                $proposal->batas_perbaikan_at = null;
                $this->pindahStatus($proposal, StatusProposal::Diajukan, $oleh, 'Perbaikan dikirim ulang.');

                return;
            }

            $proposal->save();
            $this->catat($proposal, $proposal->status, $proposal->status, $oleh, 'Dikirim untuk pengesahan.');
        });
    }

    /**
     * The faculty or programme head endorses (lembar pengesahan).
     */
    public function sahkan(Proposal $proposal, User $oleh): void
    {
        $this->pastikan($proposal->menungguPengesahan(), 'Proposal ini tidak sedang menunggu pengesahan.');

        DB::transaction(function () use ($proposal, $oleh) {
            $proposal->disahkan_oleh = $oleh->id;
            $proposal->disahkan_at = now();
            $proposal->diajukan_at = now();
            $this->pindahStatus($proposal, StatusProposal::Diajukan, $oleh, 'Disahkan.');
        });
    }

    /**
     * The faculty or programme head sends it back to the lead without endorsing.
     */
    public function tolakPengesahan(Proposal $proposal, User $oleh, string $catatan): void
    {
        $this->pastikan($proposal->menungguPengesahan(), 'Proposal ini tidak sedang menunggu pengesahan.');

        DB::transaction(function () use ($proposal, $oleh, $catatan) {
            $proposal->dikirim_at = null;
            $proposal->save();
            $this->catat($proposal, $proposal->status, $proposal->status, $oleh, 'Pengesahan ditolak: '.$catatan);
        });
    }

    /**
     * LPPM passes the administrative screening.
     */
    public function lolosAdministrasi(Proposal $proposal, User $oleh, ?string $catatan = null): void
    {
        $this->pindahStatus($proposal, StatusProposal::Ditinjau, $oleh, $catatan ?: 'Lolos seleksi administrasi.');
    }

    /**
     * LPPM returns the proposal for revision until the given deadline.
     */
    public function kembalikan(Proposal $proposal, User $oleh, string $catatan, Carbon $batas): void
    {
        $this->pastikan($batas->isFuture(), 'Batas perbaikan harus setelah hari ini.');

        DB::transaction(function () use ($proposal, $oleh, $catatan, $batas) {
            $proposal->batas_perbaikan_at = $batas;
            $this->pindahStatus($proposal, StatusProposal::Dikembalikan, $oleh, $catatan);
        });
    }

    /**
     * LPPM rejects the proposal outright.
     */
    public function tolak(Proposal $proposal, User $oleh, string $catatan): void
    {
        $this->pindahStatus($proposal, StatusProposal::TidakDidanai, $oleh, $catatan);
    }

    /**
     * Close returned proposals whose revision deadline has passed. Returns how many.
     */
    public function tutupYangLewatBatas(): int
    {
        $lewat = Proposal::where('status', StatusProposal::Dikembalikan)
            ->where('batas_perbaikan_at', '<', now())
            ->get();

        foreach ($lewat as $proposal) {
            $this->pindahStatus($proposal, StatusProposal::TidakDidanai, null, 'Batas waktu perbaikan lewat.');
        }

        return $lewat->count();
    }

    /**
     * @return list<string>
     */
    private function pelanggaranKuota(Proposal $proposal): array
    {
        $periode = $proposal->periode;
        $masalah = [];

        $sebagaiKetua = Proposal::where('periode_id', $periode->id)
            ->where('ketua_id', $proposal->ketua_id)
            ->whereKeyNot($proposal->id)
            ->where(fn ($q) => $q->whereNotNull('dikirim_at')->orWhere('status', '!=', StatusProposal::Draft))
            ->where('status', '!=', StatusProposal::TidakDidanai)
            ->count();
        if ($sebagaiKetua >= $periode->maks_sebagai_ketua) {
            $masalah[] = "Ketua sudah mengajukan {$sebagaiKetua} proposal sebagai ketua di periode ini (maksimal {$periode->maks_sebagai_ketua}).";
        }

        foreach ($proposal->anggota->where('tipe', TipeAnggota::Dosen)->whereNull('ditolak_at') as $anggota) {
            $sebagaiAnggota = Proposal::where('periode_id', $periode->id)
                ->whereKeyNot($proposal->id)
                ->where(fn ($q) => $q->whereNotNull('dikirim_at')->orWhere('status', '!=', StatusProposal::Draft))
                ->where('status', '!=', StatusProposal::TidakDidanai)
                ->whereHas('anggota', fn ($q) => $q->where('dosen_id', $anggota->dosen_id)->whereNull('ditolak_at'))
                ->count();
            if ($sebagaiAnggota >= $periode->maks_sebagai_anggota) {
                $masalah[] = "{$anggota->nama} sudah menjadi anggota di {$sebagaiAnggota} proposal periode ini (maksimal {$periode->maks_sebagai_anggota}).";
            }
        }

        return $masalah;
    }

    private function pindahStatus(Proposal $proposal, StatusProposal $tujuan, ?User $oleh, ?string $catatan): void
    {
        $asal = $proposal->status;
        $this->pastikan(
            $asal->bolehMenjadiStatus($tujuan),
            "Status {$asal->label()} tidak bisa diubah menjadi {$tujuan->label()}.",
        );

        DB::transaction(function () use ($proposal, $asal, $tujuan, $oleh, $catatan) {
            $proposal->status = $tujuan;
            $proposal->save();
            $this->catat($proposal, $asal, $tujuan, $oleh, $catatan);
        });
    }

    private function catat(Proposal $proposal, ?StatusProposal $dari, StatusProposal $ke, ?User $oleh, ?string $catatan): void
    {
        $proposal->riwayatStatus()->create([
            'status_dari' => $dari,
            'status_ke' => $ke,
            'oleh' => $oleh?->id,
            'catatan' => $catatan,
        ]);
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['proposal' => $pesan]);
        }
    }
}
