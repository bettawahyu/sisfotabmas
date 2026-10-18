<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Services\Impor\Impor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bulk loading of study programmes and the science classification from CSV,
 * and of user accounts from Excel: upload, review every row, then save.
 * Nothing is saved while any row has an error.
 */
class ImporController extends Controller
{
    public function create(string $jenis): View
    {
        return view('sistem.impor.form', ['jenis' => $jenis, 'impor' => $this->impor($jenis), 'hasil' => null]);
    }

    public function templat(string $jenis): Response
    {
        return $this->impor($jenis)->unduhTemplat($jenis);
    }

    public function periksa(Request $request, string $jenis): View
    {
        $impor = $this->impor($jenis);
        $request->validate(['berkas' => ['required', 'file', ...$impor->aturanBerkas(), 'max:1024']]);

        $baris = $impor->baca($request->file('berkas'));
        if (! $baris) {
            throw ValidationException::withMessages(['berkas' => 'Berkas tidak berisi baris data.']);
        }
        if ($hilang = $impor->kolomHilang($baris)) {
            throw ValidationException::withMessages(['berkas' => 'Kolom berikut tidak ada di berkas: '.implode(', ', $hilang).'. Gunakan templat.']);
        }
        if (count($baris) > Impor::MAKS_BARIS) {
            throw ValidationException::withMessages(['berkas' => 'Berkas paling banyak '.Impor::MAKS_BARIS.' baris.']);
        }

        $hasil = $impor->periksa($baris);
        $request->session()->put("impor.{$jenis}", $hasil);

        return view('sistem.impor.form', ['jenis' => $jenis, 'impor' => $impor, 'hasil' => $hasil]);
    }

    public function simpan(Request $request, string $jenis): RedirectResponse
    {
        $impor = $this->impor($jenis);
        $hasil = $request->session()->pull("impor.{$jenis}");

        if (! $hasil) {
            return redirect()->route('sistem.impor.create', $jenis)->withErrors(['berkas' => 'Unggah berkas lagi sebelum menyimpan.']);
        }
        if (collect($hasil)->contains(fn ($b) => $b['galat'])) {
            return redirect()->route('sistem.impor.create', $jenis)->withErrors(['berkas' => 'Masih ada baris bergalat. Perbaiki berkas lalu unggah lagi.']);
        }

        $jumlah = $impor->simpan($hasil);

        return redirect()->route($impor->ruteKembali())->with('status', "{$jumlah} baris berhasil diimpor.");
    }

    private function impor(string $jenis): Impor
    {
        return Impor::untuk($jenis) ?? abort(404);
    }
}
