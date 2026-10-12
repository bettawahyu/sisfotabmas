<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use App\Models\KriteriaPenilaian;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The substance review of a proposal in selection: LPPM assigns two reviewers,
 * each scores the proposal blind against the call's rubric and sends it in.
 */
class AlurReview
{
    public function __construct(private Pemberitahu $pemberitahu) {}

    /**
     * The selection rubric of the proposal's call, in display order.
     *
     * @return Collection<int, KriteriaPenilaian>
     */
    public function kriteria(Proposal $proposal): Collection
    {
        return $proposal->periode->kriteria()->where('tahap', Penilaian::SELEKSI)->orderBy('urutan')->get();
    }

    /**
     * Put a reviewer in slot 1 or 2. A slot whose review was not sent yet can be
     * given to someone else; a sent one must be reopened first.
     */
    public function tugaskan(Proposal $proposal, User $reviewer, int $urutan, User $oleh): Penilaian
    {
        $this->pastikan($proposal->status === StatusProposal::Ditinjau, 'Reviewer hanya bisa ditugaskan untuk proposal berstatus Direview.');
        $this->pastikan($urutan >= 1 && $urutan <= Penilaian::JUMLAH_REVIEWER, 'Proposal hanya dinilai oleh dua reviewer.');
        $this->pastikan($reviewer->hasRole(Role::REVIEWER), 'Pengguna itu tidak berperan sebagai reviewer.');
        $this->pastikan(! $this->anggotaTim($proposal, $reviewer), 'Reviewer tidak boleh ketua atau anggota proposal yang dinilainya.');
        $this->pastikan($this->kriteria($proposal)->isNotEmpty(), 'Isi kriteria penilaian seleksi di periode hibah ini terlebih dahulu.');

        $penilaianLain = $proposal->penilaian()->where('tahap', Penilaian::SELEKSI);
        $this->pastikan(
            (clone $penilaianLain)->where('reviewer_id', $reviewer->id)->where('urutan_reviewer', '!=', $urutan)->doesntExist(),
            'Reviewer itu sudah ditugaskan di slot lain proposal ini.',
        );

        $slot = (clone $penilaianLain)->where('urutan_reviewer', $urutan)->first();
        if ($slot?->reviewer_id === $reviewer->id) {
            return $slot;
        }
        $this->pastikan(! $slot?->terkirim(), 'Penilaian di slot ini sudah dikirim. Buka kembali dulu jika ingin mengganti reviewer.');

        $penilaian = DB::transaction(function () use ($proposal, $reviewer, $urutan, $slot) {
            $slot?->delete();

            return $proposal->penilaian()->create([
                'reviewer_id' => $reviewer->id,
                'tahap' => Penilaian::SELEKSI,
                'urutan_reviewer' => $urutan,
                'status' => Penilaian::DITUGASKAN,
            ]);
        });

        $batas = $proposal->periode->tgl_seleksi_selesai;
        $this->pemberitahu->keUser($reviewer, 'Tugas review proposal baru',
            'Anda ditugaskan menilai proposal "'.$proposal->judul.'"'.($batas ? ' paling lambat '.$batas->translatedFormat('j F Y') : '').'.',
            route('review.show', $penilaian));

        return $penilaian;
    }

    /**
     * Take a reviewer off a proposal before they sent their review.
     */
    public function batalkan(Penilaian $penilaian): void
    {
        $this->pastikan(! $penilaian->terkirim(), 'Penilaian yang sudah dikirim tidak bisa dibatalkan.');

        $penilaian->delete();
    }

    /**
     * Same-programme reviewers are allowed, but LPPM is warned.
     */
    public function peringatan(Proposal $proposal, User $reviewer): ?string
    {
        $prodiReviewer = $reviewer->dosen?->prodi_id;
        if ($prodiReviewer !== null && $prodiReviewer === $proposal->ketua->prodi_id) {
            return "{$reviewer->name} berasal dari program studi yang sama dengan ketua.";
        }

        return null;
    }

    /**
     * Save the reviewer's scores; with $kirim the review is sent and locked.
     *
     * @param  array<int|string, int|string>  $skor  kriteria id => score
     * @param  array<string, mixed>  $isian  rekomendasi, komentar_untuk_pengusul, catatan_internal, rekomendasi_dana
     */
    public function simpan(Penilaian $penilaian, array $skor, array $isian, bool $kirim): void
    {
        $this->pastikan(! $penilaian->terkirim(), 'Penilaian ini sudah dikirim dan terkunci.');
        $this->pastikan($penilaian->proposal->status === StatusProposal::Ditinjau, 'Proposal ini sudah tidak dalam tahap penilaian.');

        $kriteria = $this->kriteria($penilaian->proposal);
        $pesan = [];
        foreach ($kriteria as $k) {
            $nilai = $skor[$k->id] ?? null;
            if ($nilai === null || $nilai === '') {
                if ($kirim) {
                    $pesan[] = "Isi nilai untuk kriteria {$k->nama}.";
                }

                continue;
            }
            if (! is_numeric($nilai) || (int) $nilai != $nilai || $nilai < 1 || $nilai > $k->skala_maks) {
                $pesan[] = "Nilai {$k->nama} harus bilangan bulat 1 sampai {$k->skala_maks}.";
            }
        }
        if ($kirim && blank($isian['rekomendasi'] ?? null)) {
            $pesan[] = 'Pilih rekomendasi.';
        }
        if ($kirim && blank($isian['komentar_untuk_pengusul'] ?? null)) {
            $pesan[] = 'Tulis komentar untuk pengusul.';
        }
        if ($pesan !== []) {
            throw ValidationException::withMessages(['penilaian' => $pesan]);
        }

        DB::transaction(function () use ($penilaian, $kriteria, $skor, $isian, $kirim) {
            foreach ($kriteria as $k) {
                $nilai = $skor[$k->id] ?? null;
                if ($nilai === null || $nilai === '') {
                    $penilaian->nilai()->where('kriteria_id', $k->id)->delete();

                    continue;
                }
                $penilaian->nilai()->updateOrCreate(['kriteria_id' => $k->id], ['skor' => (int) $nilai]);
            }

            $penilaian->fill([
                'rekomendasi' => $isian['rekomendasi'] ?? null,
                'komentar_untuk_pengusul' => $isian['komentar_untuk_pengusul'] ?? null,
                'catatan_internal' => $isian['catatan_internal'] ?? null,
                'rekomendasi_dana' => $isian['rekomendasi_dana'] ?? null,
                'nilai_total' => $this->hitungTotal($penilaian->load('nilai'), $kriteria),
            ]);
            if ($kirim) {
                $penilaian->status = Penilaian::SELESAI;
                $penilaian->dinilai_at = now();
            }
            $penilaian->save();
        });

        if ($kirim) {
            $this->pemberitahu->kePeran([Role::ADMIN_LPPM], 'Penilaian proposal dikirim',
                "Reviewer {$penilaian->urutan_reviewer} mengirim penilaian proposal \"{$penilaian->proposal->judul}\".",
                route('admin.review.show', $penilaian->proposal));
        }
    }

    /**
     * LPPM unlocks a sent review so the reviewer can change it.
     */
    public function bukaKembali(Penilaian $penilaian, string $alasan): void
    {
        $this->pastikan($penilaian->terkirim(), 'Penilaian ini belum dikirim.');
        $this->pastikan($penilaian->proposal->status === StatusProposal::Ditinjau, 'Proposal ini sudah tidak dalam tahap penilaian.');

        $penilaian->forceFill(['status' => Penilaian::DITUGASKAN, 'dinilai_at' => null])->save();

        $this->pemberitahu->keUser($penilaian->reviewer, 'Penilaian dibuka kembali',
            "LPPM membuka kembali penilaian Anda untuk proposal \"{$penilaian->proposal->judul}\". Alasan: {$alasan}",
            route('review.show', $penilaian));
    }

    /**
     * Remind reviewers three days before the selection deadline. Returns how many.
     */
    public function ingatkanBatasSeleksi(): int
    {
        $belum = Penilaian::where('tahap', Penilaian::SELEKSI)
            ->where('status', Penilaian::DITUGASKAN)
            ->whereHas('proposal', fn ($q) => $q->where('status', StatusProposal::Ditinjau)
                ->whereHas('periode', fn ($p) => $p->whereDate('tgl_seleksi_selesai', today()->addDays(3))))
            ->with('reviewer', 'proposal.periode')
            ->get();

        foreach ($belum as $penilaian) {
            $this->pemberitahu->keUser($penilaian->reviewer, 'Pengingat batas penilaian',
                "Penilaian proposal \"{$penilaian->proposal->judul}\" ditutup {$penilaian->proposal->periode->tgl_seleksi_selesai->translatedFormat('j F Y')}.",
                route('review.show', $penilaian));
        }

        return $belum->count();
    }

    /**
     * Weighted score on a 0-100 scale: each score as a share of its scale, times its weight.
     *
     * @param  Collection<int, KriteriaPenilaian>  $kriteria
     */
    public function hitungTotal(Penilaian $penilaian, Collection $kriteria): ?float
    {
        $totalBobot = $kriteria->sum('bobot');
        if ($totalBobot <= 0 || $penilaian->nilai->isEmpty()) {
            return null;
        }

        $jumlah = 0.0;
        foreach ($kriteria as $k) {
            $nilai = $penilaian->nilai->firstWhere('kriteria_id', $k->id);
            if ($nilai) {
                $jumlah += $nilai->skor / $k->skala_maks * $k->bobot;
            }
        }

        return round($jumlah / $totalBobot * 100, 2);
    }

    private function anggotaTim(Proposal $proposal, User $reviewer): bool
    {
        $dosen = $reviewer->dosen;
        if ($dosen === null) {
            return false;
        }

        return $proposal->ketua_id === $dosen->id
            || $proposal->anggota()->where('tipe', TipeAnggota::Dosen)->where('dosen_id', $dosen->id)->exists();
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['penilaian' => $pesan]);
        }
    }
}
