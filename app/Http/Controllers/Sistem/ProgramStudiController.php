<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Study programmes are listed on the Unit page under their faculty.
 */
class ProgramStudiController extends Controller
{
    public function create(Request $request): View
    {
        return view('sistem.unit.prodi', [
            'prodi' => new ProgramStudi(['fakultas_id' => $request->integer('fakultas') ?: null, 'jenjang' => 'S1', 'is_active' => true]),
            'fakultas' => Fakultas::aktif()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProgramStudi::create($this->validasi($request));

        return redirect()->route('sistem.fakultas.index')->with('status', 'Program studi ditambahkan.');
    }

    public function edit(ProgramStudi $prodi): View
    {
        return view('sistem.unit.prodi', [
            'prodi' => $prodi,
            'fakultas' => Fakultas::aktifAtau($prodi->fakultas_id)->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, ProgramStudi $prodi): RedirectResponse
    {
        $prodi->update($this->validasi($request, $prodi));

        return redirect()->route('sistem.fakultas.index')->with('status', 'Program studi diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?ProgramStudi $prodi = null): array
    {
        return $request->validate([
            'fakultas_id' => ['required', Rule::exists('fakultas', 'id')
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $prodi?->fakultas_id))],
            'kode' => ['required', 'string', 'max:20', Rule::unique('program_studi')->ignore($prodi)],
            'nama' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(ProgramStudi::JENJANG)],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
