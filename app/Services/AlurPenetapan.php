<?php

namespace App\Services;

use App\Enums\StatusProposal;
use App\Models\Penilaian;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * LPPM ranks a call's reviewed proposals by the average of their two reviewers,
 * decides which are funded within the quota, then issues one SK for all of them.
 */
class AlurPenetapan
{
    public function __construct(private AlurProposal $alur, private Pemberitahu $pemberitahu) {}

    /**
     * The call's proposals under review, best average first; proposals still
     * missing a review come last with a null average.
     *
     * @return Collection<int, Proposal>
     */
    public function peringkat(PeriodeHibah $periode): Collection
    {
        return Proposal::where('periode_id', $periode->id)
            ->where('status', StatusProposal::Ditinjau)
            ->with(['ketua', 'penilaian' => fn ($q) => $q->where('tahap', Penilaian::SELEKSI)])
            ->get()
            ->each(function (Proposal $p) {
                $terkirim = $p->penilaian->where('status', Penilaian::SELESAI);
                $p->setAttribute('rata_rata', $terkirim->count() === Penilaian::JUMLAH_REVIEWER ? round((float) $terkirim->avg('nilai_total'), 2) : null);
                $dana = $terkirim->whereNotNull('rekomendasi_dana');
                $p->setAttribute('rekomendasi_dana_rata', $dana->isNotEmpty() ? (int) round($dana->avg('rekomendasi_dana')) : null);
            })
            ->sortBy([fn ($a, $b) => ($b->rata_rata ?? -1) <=> ($a->rata_rata ?? -1)])
            ->values();
    }

    /**
     * Decide the whole call: the chosen proposals are approved with their
     * approved amount, every other reviewed proposal is not funded.
     *
     * @param  array<int|string, int|string>  $didanai  proposal id => approved amount
     */
    public function tetapkan(PeriodeHibah $periode, array $didanai, User $oleh): void
    {
        $peringkat = $this->peringkat($periode);
        $this->pastikan($peringkat->isNotEmpty(), 'Tidak ada proposal yang menunggu penetapan di periode ini.');
        $this->pastikan($peringkat->whereNull('rata_rata')->isEmpty(), 'Masih ada proposal yang belum dinilai lengkap oleh dua reviewer.');

        $pilihan = collect($didanai)->mapWithKeys(fn ($dana, $id) => [(int) $id => $dana]);
        $this->pastikan($pilihan->keys()->diff($peringkat->modelKeys())->isEmpty(), 'Ada proposal terpilih yang bukan bagian dari penetapan ini.');
        $this->pastikan($periode->kuota === null || $pilihan->count() <= $periode->kuota, "Jumlah proposal didanai melebihi kuota {$periode->kuota}.");

        $pesan = [];
        foreach ($peringkat->whereIn('id', $pilihan->keys()) as $p) {
            $dana = $pilihan[$p->id];
            if (! is_numeric($dana) || (int) $dana < 1 || (int) $dana > $p->dana_diusulkan) {
                $pesan[] = "Dana disetujui untuk \"{$p->judul}\" harus antara Rp 1 dan dana diusulkan.";
            }
        }
        if ($pesan !== []) {
            throw ValidationException::withMessages(['penetapan' => $pesan]);
        }

        DB::transaction(function () use ($peringkat, $pilihan, $oleh) {
            foreach ($peringkat as $i => $p) {
                $isDidanai = $pilihan->has($p->id);
                $p->forceFill([
                    'nilai_akhir' => $p->rata_rata,
                    'peringkat' => $i + 1,
                    'dana_disetujui' => $isDidanai ? (int) $pilihan[$p->id] : null,
                ]);
                $p->offsetUnset('rata_rata');
                $p->offsetUnset('rekomendasi_dana_rata');
                $this->alur->tetapkanHasil($p, $isDidanai, $oleh,
                    $isDidanai ? 'Ditetapkan didanai.' : 'Tidak terpilih dalam penetapan pendanaan.');
            }
        });

        foreach ($peringkat as $p) {
            $didanaiIni = $pilihan->has($p->id);
            $this->pemberitahu->keKetua($p,
                $didanaiIni ? 'Proposal disetujui untuk didanai' : 'Proposal tidak didanai',
                ($didanaiIni
                    ? "Proposal \"{$p->judul}\" disetujui dengan dana Rp ".number_format($p->dana_disetujui, 0, ',', '.').'.'
                    : "Proposal \"{$p->judul}\" belum terpilih untuk didanai pada periode ini.")
                .' Komentar reviewer bisa dibaca di halaman proposal.');
        }
    }

    /**
     * Put one SK number and date on every approved proposal of the call that
     * has none yet. Returns how many proposals it covered.
     */
    public function terbitkanSk(PeriodeHibah $periode, string $nomor, Carbon $tanggal): int
    {
        $disetujui = Proposal::where('periode_id', $periode->id)
            ->where('status', StatusProposal::Disetujui)
            ->whereNull('no_sk')
            ->get();
        $this->pastikan($disetujui->isNotEmpty(), 'Tidak ada proposal disetujui yang belum punya SK.');

        foreach ($disetujui as $p) {
            $p->forceFill(['no_sk' => $nomor, 'tgl_sk' => $tanggal])->save();
            $this->pemberitahu->keKetua($p, 'SK pendanaan terbit',
                "SK nomor {$nomor} tanggal {$tanggal->translatedFormat('j F Y')} sudah terbit untuk proposal \"{$p->judul}\".");
        }

        return $disetujui->count();
    }

    private function pastikan(bool $kondisi, string $pesan): void
    {
        if (! $kondisi) {
            throw ValidationException::withMessages(['penetapan' => $pesan]);
        }
    }
}
