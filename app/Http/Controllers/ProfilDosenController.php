<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilDosenController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dosen.profil', [
            'dosen' => $request->user()->dosen,
            'prodi' => ProgramStudi::with('fakultas')->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $id = $user->dosen?->id;

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:100'],
            'nidn' => ['nullable', 'string', 'max:20', Rule::unique('dosen')->ignore($id)],
            'nuptk' => ['nullable', 'string', 'max:20', Rule::unique('dosen')->ignore($id)],
            'sinta_id' => ['nullable', 'string', 'max:20'],
            'prodi_id' => ['nullable', 'exists:program_studi,id'],
            'jabatan_fungsional' => ['nullable', 'string', 'max:50'],
        ]);

        $user->dosen()->updateOrCreate([], $data);

        return redirect()->route('profil-dosen.edit')->with('status', 'Profil dosen tersimpan.');
    }
}
