<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\PeringkatJurnal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Ranks a lecturer files a publication under, such as SINTA 2 or Scopus Q1.
 */
class PeringkatJurnalController extends Controller
{
    public function index(): View
    {
        return view('sistem.peringkat-jurnal.index', [
            'peringkat' => PeringkatJurnal::withCount('publikasi')->berurutan()->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistem.peringkat-jurnal.form', [
            'peringkat' => new PeringkatJurnal(['urutan' => (PeringkatJurnal::max('urutan') ?? 0) + 10, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PeringkatJurnal::create($this->validasi($request));

        return redirect()->route('sistem.peringkat-jurnal.index')->with('status', 'Peringkat jurnal ditambahkan.');
    }

    public function edit(PeringkatJurnal $peringkatJurnal): View
    {
        return view('sistem.peringkat-jurnal.form', ['peringkat' => $peringkatJurnal]);
    }

    public function update(Request $request, PeringkatJurnal $peringkatJurnal): RedirectResponse
    {
        $data = $this->validasi($request, $peringkatJurnal);
        // Publications point at the code, so it stays fixed once in use.
        if ($peringkatJurnal->publikasi()->exists()) {
            unset($data['kode']);
        }
        $peringkatJurnal->update($data);

        return redirect()->route('sistem.peringkat-jurnal.index')->with('status', 'Peringkat jurnal diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?PeringkatJurnal $peringkat = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_]+$/', Rule::unique('peringkat_jurnal')->ignore($peringkat)],
            'nama' => ['required', 'string', 'max:255'],
            'urutan' => ['required', 'integer', 'between:0,65535'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
