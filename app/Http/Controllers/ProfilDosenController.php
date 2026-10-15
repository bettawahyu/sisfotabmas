<?php

namespace App\Http\Controllers;

use App\Enums\JabatanFungsional;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilDosenController extends Controller
{
    public function edit(Request $request): View
    {
        $dosen = $request->user()->dosen;

        return view('dosen.profil', [
            'dosen' => $dosen,
            'prodi' => ProgramStudi::aktifAtau($dosen?->prodi_id)->with('fakultas')->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $id = $user->dosen?->id;

        // Accept a full profile URL as well as a bare ID; only the ID is stored.
        $scholar = trim((string) $request->input('google_scholar_id'));
        if (preg_match('/[?&]user=([^&#]+)/', $scholar, $m)) {
            $scholar = $m[1];
        }
        $request->merge(['google_scholar_id' => $scholar === '' ? null : $scholar]);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:100'],
            'nidn' => ['nullable', 'string', 'max:20', Rule::unique('dosen')->ignore($id)],
            'nuptk' => ['nullable', 'string', 'max:20', Rule::unique('dosen')->ignore($id)],
            'sinta_id' => ['nullable', 'string', 'max:20'],
            'google_scholar_id' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            // A programme switched off is no longer offered, but a lecturer already in it keeps it.
            'prodi_id' => ['nullable', Rule::exists('program_studi', 'id')
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $user->dosen?->prodi_id))],
            'jabatan_fungsional' => ['nullable', Rule::enum(JabatanFungsional::class)],
        ]);

        $user->dosen()->updateOrCreate([], $data);

        return redirect()->route('profil-dosen.edit')->with('status', 'Kualifikasi dosen tersimpan.');
    }
}
