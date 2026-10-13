<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * LPPM ties each faculty or programme head to the unit whose proposals they endorse.
 */
class PimpinanUnitController extends Controller
{
    public function index(): View
    {
        return view('admin.pimpinan-unit.index', [
            'pimpinan' => User::whereHas('roles', fn ($q) => $q->where('kode', Role::PIMPINAN_UNIT))->with('roles')->orderBy('name')->get(),
            'fakultas' => Fakultas::with(['programStudi' => fn ($q) => $q->orderBy('nama')])->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(Role::PIMPINAN_UNIT), 404);
        $unit = $request->validate(['unit' => ['nullable', 'regex:/^(prodi|fakultas):\d+$/']])['unit'] ?? null;

        [$jenis, $id] = $unit ? explode(':', $unit) : [null, null];
        $tabel = ['prodi' => 'program_studi', 'fakultas' => 'fakultas'];
        if ($jenis && ! DB::table($tabel[$jenis])->where('id', $id)->exists()) {
            return back()->withErrors(['unit' => 'Unit tidak ditemukan.']);
        }

        $user->roles()->updateExistingPivot(Role::where('kode', Role::PIMPINAN_UNIT)->value('id'), [
            'prodi_id' => $jenis === 'prodi' ? (int) $id : null,
            'fakultas_id' => $jenis === 'fakultas' ? (int) $id : null,
        ]);

        return back()->with('status', "Unit {$user->name} diperbarui.");
    }
}
