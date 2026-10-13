<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KriteriaPenilaian;
use App\Models\Penilaian;
use App\Models\PeriodeHibah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The rubric of a call, kept per call because it can change from year to year.
 */
class KriteriaPenilaianController extends Controller
{
    public function store(Request $request, PeriodeHibah $periode): RedirectResponse
    {
        $data = $request->validate([
            'tahap' => ['required', Rule::in([Penilaian::SELEKSI, Penilaian::MONEV, Penilaian::AKHIR])],
            'nama' => ['required', 'string', 'max:255'],
            'bobot' => ['required', 'integer', 'between:1,100'],
            'skala_maks' => ['required', 'integer', 'between:2,10'],
        ]);
        $data['urutan'] = $periode->kriteria()->where('tahap', $data['tahap'])->max('urutan') + 1;

        $periode->kriteria()->create($data);

        return back()->with('status', 'Kriteria ditambahkan.');
    }

    public function destroy(PeriodeHibah $periode, KriteriaPenilaian $kriteria): RedirectResponse
    {
        abort_unless($kriteria->periode_id === $periode->id, 404);
        if ($kriteria->nilai()->exists()) {
            return back()->withErrors(['kriteria' => 'Kriteria ini sudah dipakai reviewer dan tidak bisa dihapus.']);
        }

        $kriteria->delete();

        return back()->with('status', 'Kriteria dihapus.');
    }
}
