<?php

namespace App\Http\Controllers;

use App\Enums\JabatanFungsional;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilDosenController extends Controller
{
    public function edit(Request $request): View
    {
        $dosen = $request->user()->dosen?->load('riwayatPendidikan');

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
        // ORCID is also often pasted as its full https://orcid.org/... link.
        $orcid = preg_replace('#^https?://(www\.)?orcid\.org/#i', '', trim((string) $request->input('orcid')));
        $request->merge([
            'google_scholar_id' => $scholar === '' ? null : $scholar,
            'orcid' => $orcid === '' ? null : strtoupper($orcid),
            'sertifikasi_dosen' => $request->boolean('sertifikasi_dosen'),
            'pekerti' => $request->boolean('pekerti'),
            'applied_approach' => $request->boolean('applied_approach'),
        ]);

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
            'orcid' => ['nullable', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'bidang_keahlian' => ['nullable', 'string', 'max:255'],
            'tmt_dosen' => ['nullable', 'date', 'before_or_equal:today'],
            'sertifikasi_dosen' => ['boolean'],
            'no_serdos' => ['nullable', 'required_if:sertifikasi_dosen,true', 'string', 'max:30'],
            'tahun_serdos' => ['nullable', 'required_if:sertifikasi_dosen,true', 'integer', 'between:1990,'.now()->year],
            'pekerti' => ['boolean'],
            'tahun_pekerti' => ['nullable', 'integer', 'between:1980,'.now()->year],
            'applied_approach' => ['boolean'],
            'tahun_applied_approach' => ['nullable', 'integer', 'between:1980,'.now()->year],
            'alamat' => ['nullable', 'string', 'max:500'],
            'no_hp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'jenis_kelamin' => ['nullable', Rule::in(array_keys(Dosen::JENIS_KELAMIN))],
            'status_pernikahan' => ['nullable', Rule::in(array_keys(Dosen::STATUS_PERNIKAHAN))],
        ], [
            'no_hp.regex' => 'Nomor telepon hanya berisi angka, spasi, +, -, atau tanda kurung.',
            'orcid.regex' => 'ORCID ditulis 0000-0000-0000-0000.',
            'no_serdos.required_if' => 'Isi nomor sertifikat pendidik bila sudah tersertifikasi.',
            'tahun_serdos.required_if' => 'Isi tahun sertifikasi bila sudah tersertifikasi.',
        ]);

        // A status set back to "belum" drops the details that went with it.
        if (! $data['sertifikasi_dosen']) {
            $data['no_serdos'] = $data['tahun_serdos'] = null;
        }
        if (! $data['pekerti']) {
            $data['tahun_pekerti'] = null;
        }
        if (! $data['applied_approach']) {
            $data['tahun_applied_approach'] = null;
        }

        // Saving the lecturer also renames the account (Dosen::booted).
        $user->dosen()->updateOrCreate([], $data);

        return redirect()->route('profil-dosen.edit')->with('status', 'Profil dosen tersimpan.');
    }
}
