<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeriode;
use App\Http\Controllers\Controller;
use App\Models\PeriodeHibah;
use App\Models\SkemaHibah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Each year's call. Ceilings and the honorarium rule are copied from the
 * scheme when the call is created and can be changed here, since they change
 * from year to year.
 */
class PeriodeHibahController extends Controller
{
    public function index(): View
    {
        return view('admin.periode.index', [
            'periode' => PeriodeHibah::with('skema')->withCount('proposal')->orderByDesc('tahun_anggaran')->orderBy('tgl_buka')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $skema = SkemaHibah::where('is_active', true)->orderBy('nama')->get();
        $dipilih = $skema->firstWhere('id', $request->integer('skema_id'));

        return view('admin.periode.form', [
            'daftarSkema' => $skema,
            'periode' => new PeriodeHibah([
                'skema_id' => $dipilih?->id,
                'tahun_anggaran' => now()->year,
                'dana_maksimal' => $dipilih?->dana_maksimal_default,
                'honor_diizinkan' => $dipilih?->honor_diizinkan ?? false,
                'batas_honor_persen' => $dipilih?->batas_honor_persen,
                'termin_pertama_persen' => 80,
                'maks_sebagai_ketua' => 1,
                'maks_sebagai_anggota' => 2,
                'status' => StatusPeriode::Draft,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PeriodeHibah::create($this->validasi($request));

        return redirect()->route('admin.periode.index')->with('status', 'Periode hibah dibuat.');
    }

    public function edit(PeriodeHibah $periode): View
    {
        return view('admin.periode.form', [
            'daftarSkema' => SkemaHibah::orderBy('nama')->get(),
            'periode' => $periode,
        ]);
    }

    public function update(Request $request, PeriodeHibah $periode): RedirectResponse
    {
        $periode->update($this->validasi($request));

        return redirect()->route('admin.periode.index')->with('status', 'Periode hibah diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'skema_id' => ['required', 'exists:skema_hibah,id'],
            'tahun_anggaran' => ['required', 'integer', 'between:2000,2100'],
            'nama' => ['required', 'string', 'max:255'],
            'tgl_buka' => ['required', 'date'],
            'tgl_tutup' => ['required', 'date', 'after_or_equal:tgl_buka'],
            'tgl_seleksi_selesai' => ['nullable', 'date', 'after_or_equal:tgl_tutup'],
            'tgl_pengumuman' => ['nullable', 'date'],
            'tgl_laporan_kemajuan' => ['nullable', 'date'],
            'tgl_monev' => ['nullable', 'date'],
            'tgl_laporan_akhir' => ['nullable', 'date'],
            'kuota' => ['nullable', 'integer', 'min:1'],
            'dana_maksimal' => ['required', 'integer', 'min:1'],
            'honor_diizinkan' => ['boolean'],
            'batas_honor_persen' => ['nullable', 'integer', 'between:1,100'],
            'termin_pertama_persen' => ['required', 'integer', 'between:1,100'],
            'maks_sebagai_ketua' => ['required', 'integer', 'min:1'],
            'maks_sebagai_anggota' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(StatusPeriode::class)],
        ]);
        $data['honor_diizinkan'] = $request->boolean('honor_diizinkan');

        return $data;
    }
}
