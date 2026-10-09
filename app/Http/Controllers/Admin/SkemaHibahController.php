<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisKegiatan;
use App\Enums\SumberDana;
use App\Http\Controllers\Controller;
use App\Models\SkemaHibah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkemaHibahController extends Controller
{
    public function index(): View
    {
        return view('admin.skema.index', ['skema' => SkemaHibah::orderBy('jenis_kegiatan')->orderBy('nama')->get()]);
    }

    public function create(): View
    {
        return view('admin.skema.form', ['skema' => new SkemaHibah(['lama_maks_tahun' => 1, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        SkemaHibah::create($this->validasi($request));

        return redirect()->route('admin.skema.index')->with('status', 'Skema hibah dibuat.');
    }

    public function edit(SkemaHibah $skema): View
    {
        return view('admin.skema.form', ['skema' => $skema]);
    }

    public function update(Request $request, SkemaHibah $skema): RedirectResponse
    {
        $skema->update($this->validasi($request, $skema));

        return redirect()->route('admin.skema.index')->with('status', 'Skema hibah diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?SkemaHibah $skema = null): array
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', Rule::unique('skema_hibah')->ignore($skema)],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kegiatan' => ['required', Rule::enum(JenisKegiatan::class)],
            'sumber_dana' => ['required', Rule::enum(SumberDana::class)],
            'is_multitahun' => ['boolean'],
            'lama_maks_tahun' => ['required', 'integer', 'between:1,5'],
            'dana_maksimal_default' => ['required', 'integer', 'min:0'],
            'tkt_min' => ['nullable', 'integer', 'between:1,9'],
            'honor_diizinkan' => ['boolean'],
            'batas_honor_persen' => ['nullable', 'integer', 'between:1,100'],
            'luaran_wajib' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        foreach (['is_multitahun', 'honor_diizinkan', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        if (! $data['is_multitahun']) {
            $data['lama_maks_tahun'] = 1;
        }

        return $data;
    }
}
