<?php

namespace App\Http\Controllers\Karya;

use App\Http\Controllers\Controller;
use App\Models\Hki;
use App\Services\AlurKarya;
use App\Services\DataDosen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Every lecturer's publications, HKI and patents for operators, LPPM and unit
 * heads; a unit head sees only their unit's. LPPM or an operator validates.
 */
class KaryaDosenController extends Controller
{
    public function index(Request $request, DataDosen $data): View
    {
        $user = $request->user();
        $verifikator = AlurKarya::bolehVerifikasi($user);
        $filter = $request->validate([
            'tab' => ['nullable', Rule::in(array_keys(AlurKarya::JENIS))],
            'status' => ['nullable', Rule::in([...array_keys(Hki::STATUS_VERIFIKASI), 'semua'])],
            'cari' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer'],
        ]);
        // Verifiers land on what waits for them.
        $filter['status'] ??= $verifikator ? Hki::MENUNGGU : 'semua';

        $karya = [];
        foreach (AlurKarya::JENIS as $jenis => $model) {
            $karya[$jenis] = $model::query()
                ->when(! DataDosen::bolehLihatSemua($user), fn (Builder $q) => $q->dalamUnit($user))
                ->when($filter['status'] !== 'semua', fn (Builder $q) => $q->where('status_verifikasi', $filter['status']))
                ->when($filter['cari'] ?? null, fn (Builder $q, $cari) => $q->where('judul', 'like', "%{$cari}%"))
                ->when($filter['prodi_id'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('kontributor.dosen', fn (Builder $d) => $d->where('prodi_id', $prodi)))
                ->with(['kontributor', 'penginput', 'verifikatorLppm'])
                ->latest('updated_at')
                ->get();
        }

        return view('karya.dosen', [
            'karya' => $karya,
            'filter' => $filter,
            'prodi' => $data->prodi($user),
            'lihatSemua' => DataDosen::bolehLihatSemua($user),
            'verifikator' => $verifikator,
        ]);
    }

    public function verifikasi(Request $request, string $jenis, int $karya, AlurKarya $alur): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $model = AlurKarya::JENIS[$jenis];
        $entri = $model::findOrFail($karya);

        $alur->verifikasi($entri, $data['aksi'] === 'setujui', $data['catatan'] ?? null, $request->user());

        return back()->with('status', $data['aksi'] === 'setujui' ? 'Data divalidasi.' : 'Data ditolak dan penginput diberi tahu.');
    }
}
