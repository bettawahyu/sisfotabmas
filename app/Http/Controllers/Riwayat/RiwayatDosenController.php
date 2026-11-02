<?php

namespace App\Http\Controllers\Riwayat;

use App\Http\Controllers\Controller;
use App\Models\RiwayatKegiatan;
use App\Services\AlurRiwayat;
use App\Services\DataDosen;
use App\Services\EksporRiwayat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every lecturer's research and community service records for operators,
 * LPPM and unit heads; a unit head sees only their unit's. LPPM or an
 * operator validates, and may enter or correct a record for anyone.
 */
class RiwayatDosenController extends Controller
{
    public function index(Request $request, string $jenis, DataDosen $data): View
    {
        $user = $request->user();
        $petugas = AlurRiwayat::petugasLppm($user);
        [$filter, $query] = $this->saring($request, $jenis);

        return view('riwayat.dosen', [
            'jenis' => $jenis,
            'riwayat' => $query->paginate(25)->withQueryString(),
            'filter' => $filter,
            'prodi' => $data->prodi($user),
            'lihatSemua' => DataDosen::bolehLihatSemua($user),
            'petugas' => $petugas,
        ]);
    }

    /**
     * The same list as the page, with its filters, as an Excel file.
     */
    public function ekspor(Request $request, string $jenis, EksporRiwayat $ekspor): StreamedResponse
    {
        [, $query] = $this->saring($request, $jenis);

        return $ekspor->unduh($ekspor->baris($query->get(), AlurRiwayat::JENIS[$jenis]), "riwayat-{$jenis}-dosen");
    }

    /**
     * The filters from the query string and the records they select.
     *
     * @return array{array<string, mixed>, Builder<RiwayatKegiatan>}
     */
    private function saring(Request $request, string $jenis): array
    {
        $user = $request->user();
        $filter = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(RiwayatKegiatan::STATUS_VERIFIKASI), 'semua'])],
            'kegiatan' => ['nullable', Rule::in(array_keys(RiwayatKegiatan::STATUS_KEGIATAN))],
            'sumber_dana' => ['nullable', Rule::in(array_keys(RiwayatKegiatan::SUMBER_DANA))],
            'tahun' => ['nullable', 'integer', 'between:1950,2100'],
            'cari' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer'],
        ]);
        // Verifiers land on what waits for them.
        $filter['status'] ??= AlurRiwayat::petugasLppm($user) ? RiwayatKegiatan::MENUNGGU : 'semua';

        $query = RiwayatKegiatan::jenis(AlurRiwayat::JENIS[$jenis])
            ->when(! DataDosen::bolehLihatSemua($user), fn (Builder $q) => $q->dalamUnit($user))
            ->when($filter['status'] !== 'semua', fn (Builder $q) => $q->where('status_verifikasi', $filter['status']))
            ->when($filter['kegiatan'] ?? null, fn (Builder $q, $status) => $q->where('status_kegiatan', $status))
            ->when($filter['sumber_dana'] ?? null, fn (Builder $q, $sumber) => $q->where('sumber_dana', $sumber))
            // Activities under way in that year; one still running with no end year runs to now.
            ->when($filter['tahun'] ?? null, fn (Builder $q, $tahun) => $q->where('tahun_mulai', '<=', $tahun)
                ->where(fn (Builder $t) => $t->where('tahun_selesai', '>=', $tahun)
                    ->orWhere(fn (Builder $s) => $s->whereNull('tahun_selesai')->where(fn (Builder $b) => $b->where('tahun_mulai', $tahun)
                        ->orWhere('status_kegiatan', RiwayatKegiatan::BERJALAN)))))
            ->when($filter['cari'] ?? null, fn (Builder $q, $cari) => $q->where(fn (Builder $w) => $w->where('judul', 'like', "%{$cari}%")
                ->orWhereHas('anggota', fn (Builder $a) => $a->where('nama', 'like', "%{$cari}%"))))
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('anggota.dosen', fn (Builder $d) => $d->where('prodi_id', $prodi)))
            ->with(['anggota.dosen', 'mitra', 'kabupatenKota.provinsi', 'berkas', 'penginput', 'verifikatorLppm', 'publikasi', 'hki', 'paten'])
            ->orderByDesc('tahun_mulai')->latest('updated_at');

        return [$filter, $query];
    }

    public function verifikasi(Request $request, string $jenis, RiwayatKegiatan $riwayat, AlurRiwayat $alur): RedirectResponse
    {
        abort_unless($riwayat->jenis_kegiatan === AlurRiwayat::JENIS[$jenis], 404);
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $alur->verifikasi($riwayat, $data['aksi'] === 'setujui', $data['catatan'] ?? null, $request->user());

        return back()->with('status', $data['aksi'] === 'setujui' ? 'Data divalidasi.' : 'Data ditolak dan penginput diberi tahu.');
    }
}
