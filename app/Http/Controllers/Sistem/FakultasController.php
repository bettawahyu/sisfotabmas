<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Faculties and, nested under each, their study programmes. The index is the Unit page.
 */
class FakultasController extends Controller
{
    public function index(): View
    {
        return view('sistem.unit.index', [
            'fakultas' => Fakultas::withCount('programStudi')
                ->with(['programStudi' => fn ($q) => $q->withCount('dosen')->orderBy('nama')])
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistem.unit.fakultas', ['fakultas' => new Fakultas(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Fakultas::create($this->validasi($request));

        return redirect()->route('sistem.fakultas.index')->with('status', 'Fakultas ditambahkan.');
    }

    public function edit(Fakultas $fakultas): View
    {
        return view('sistem.unit.fakultas', ['fakultas' => $fakultas]);
    }

    public function update(Request $request, Fakultas $fakultas): RedirectResponse
    {
        $fakultas->update($this->validasi($request, $fakultas));

        return redirect()->route('sistem.fakultas.index')->with('status', 'Fakultas diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Fakultas $fakultas = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('fakultas')->ignore($fakultas)],
            'nama' => ['required', 'string', 'max:255'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
