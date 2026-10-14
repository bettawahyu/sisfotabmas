<?php

namespace App\Http\Controllers\Karya;

use App\Http\Controllers\Controller;
use App\Models\Hki;
use App\Models\Role;
use App\Services\AlurKarya;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kaprodi and deans approve the entries of their unit's lecturers; LPPM then
 * marks them valid.
 */
class VerifikasiKaryaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menunggu = [];
        foreach (AlurKarya::JENIS as $jenis => $model) {
            $kaprodi = $user->hasRole(Role::PIMPINAN_UNIT)
                ? $model::where('status_verifikasi', Hki::MENUNGGU)->dalamUnit($user)->get()
                : new Collection;
            $lppm = $user->hasRole(Role::ADMIN_LPPM)
                ? $model::where('status_verifikasi', Hki::DISETUJUI_KAPRODI)->get()
                : new Collection;
            $menunggu[$jenis] = $kaprodi->concat($lppm)->load(['kontributor', 'penginput', 'verifikatorKaprodi'])->sortBy('updated_at');
        }

        return view('karya.verifikasi', ['menunggu' => $menunggu]);
    }

    public function store(Request $request, string $jenis, int $karya, AlurKarya $alur): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $model = AlurKarya::JENIS[$jenis];
        $entri = $model::findOrFail($karya);

        $alur->verifikasi($entri, $data['aksi'] === 'setujui', $data['catatan'] ?? null, $request->user());

        return back()->with('status', $data['aksi'] === 'setujui' ? 'Data disetujui.' : 'Data ditolak dan penginput diberi tahu.');
    }
}
