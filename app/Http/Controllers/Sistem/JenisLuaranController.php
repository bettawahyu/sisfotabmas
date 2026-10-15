<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\JenisLuaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Output types offered when a lecturer lists a proposal's target outputs, and
 * when LPPM sets the outputs a scheme requires.
 */
class JenisLuaranController extends Controller
{
    public function index(): View
    {
        return view('sistem.jenis-luaran.index', [
            'jenis' => JenisLuaran::withCount('luaran')->berurutan()->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistem.jenis-luaran.form', [
            'jenis' => new JenisLuaran([
                'berlaku_untuk' => JenisLuaran::SEMUA,
                'urutan' => (JenisLuaran::max('urutan') ?? 0) + 10,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        JenisLuaran::create($this->validasi($request));

        return redirect()->route('sistem.jenis-luaran.index')->with('status', 'Jenis luaran ditambahkan.');
    }

    public function edit(JenisLuaran $jenisLuaran): View
    {
        return view('sistem.jenis-luaran.form', ['jenis' => $jenisLuaran]);
    }

    public function update(Request $request, JenisLuaran $jenisLuaran): RedirectResponse
    {
        $data = $this->validasi($request, $jenisLuaran);
        // Proposal outputs point at the code, so it stays fixed once in use.
        if ($jenisLuaran->luaran()->exists()) {
            unset($data['kode']);
        }
        $jenisLuaran->update($data);

        return redirect()->route('sistem.jenis-luaran.index')->with('status', 'Jenis luaran diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?JenisLuaran $jenis = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('jenis_luaran')->ignore($jenis)],
            'nama' => ['required', 'string', 'max:255'],
            'berlaku_untuk' => ['required', Rule::in(array_keys(JenisLuaran::BERLAKU))],
            'tautan_karya' => ['nullable', Rule::in(array_keys(JenisLuaran::TAUTAN))],
            'urutan' => ['required', 'integer', 'between:0,65535'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
