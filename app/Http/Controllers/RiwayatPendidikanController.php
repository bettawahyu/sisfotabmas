<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\RiwayatPendidikan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A lecturer's degrees, kept on the qualification page. The highest one becomes
 * the degree a scheme's minimum education is checked against.
 */
class RiwayatPendidikanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $dosen = $request->user()->dosen;
        abort_if($dosen === null, 404);

        $dosen->riwayatPendidikan()->create($this->validasi($request));
        $dosen->perbaruiPendidikanTerakhir();

        return redirect()->route('profil-dosen.edit')->with('status', 'Riwayat pendidikan ditambahkan.');
    }

    public function edit(Request $request, RiwayatPendidikan $riwayat): View
    {
        $this->pastikanMilik($request, $riwayat);

        return view('dosen.pendidikan', ['riwayat' => $riwayat]);
    }

    public function update(Request $request, RiwayatPendidikan $riwayat): RedirectResponse
    {
        $this->pastikanMilik($request, $riwayat);

        $riwayat->update($this->validasi($request));
        $riwayat->dosen->perbaruiPendidikanTerakhir();

        return redirect()->route('profil-dosen.edit')->with('status', 'Riwayat pendidikan diperbarui.');
    }

    public function destroy(Request $request, RiwayatPendidikan $riwayat): RedirectResponse
    {
        $this->pastikanMilik($request, $riwayat);

        $riwayat->delete();
        $riwayat->dosen->perbaruiPendidikanTerakhir();

        return redirect()->route('profil-dosen.edit')->with('status', 'Riwayat pendidikan dihapus.');
    }

    private function pastikanMilik(Request $request, RiwayatPendidikan $riwayat): void
    {
        abort_unless($riwayat->dosen_id === $request->user()->dosen?->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'jenjang' => ['required', Rule::in(Dosen::PENDIDIKAN)],
            'institusi' => ['required', 'string', 'max:255'],
            'program_studi' => ['nullable', 'string', 'max:255'],
            'negara' => ['nullable', 'string', 'max:100'],
            'tahun_lulus' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'judul_tugas_akhir' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
