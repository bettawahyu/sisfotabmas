<?php

namespace App\Http\Controllers\Riwayat;

use App\Http\Controllers\Controller;
use App\Services\AlurRiwayat;
use App\Services\Impor\ImporRiwayat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * LPPM and operators load old records from Excel: download the template,
 * upload, review every row, then save. Nothing is saved while a row has an
 * error; rows that look like duplicates are skipped unless asked for.
 */
class ImporRiwayatController extends Controller
{
    public function create(Request $request, string $jenis): View
    {
        return view('riwayat.impor', ['jenis' => $jenis, 'impor' => $this->impor($request, $jenis), 'hasil' => null]);
    }

    public function templat(Request $request, string $jenis): Response
    {
        return $this->impor($request, $jenis)->unduhTemplat($jenis);
    }

    public function periksa(Request $request, string $jenis): View
    {
        $impor = $this->impor($request, $jenis);
        $request->validate(['berkas' => ['required', 'file', ...$impor->aturanBerkas(), 'max:2048']]);

        $baris = $impor->baca($request->file('berkas'));
        if (! $baris) {
            throw ValidationException::withMessages(['berkas' => 'Berkas tidak berisi baris data.']);
        }
        if ($hilang = $impor->kolomHilang($baris)) {
            throw ValidationException::withMessages(['berkas' => 'Kolom berikut tidak ada di berkas: '.implode(', ', $hilang).'. Gunakan templat '.($jenis === 'pkm' ? 'PkM' : 'penelitian').'.']);
        }
        if (count($baris) > $impor->maksBaris()) {
            throw ValidationException::withMessages(['berkas' => 'Berkas paling banyak '.$impor->maksBaris().' baris.']);
        }

        $hasil = $impor->periksa($baris);
        $request->session()->put("impor-riwayat.{$jenis}", $hasil);

        return view('riwayat.impor', ['jenis' => $jenis, 'impor' => $impor, 'hasil' => $hasil]);
    }

    public function simpan(Request $request, string $jenis): RedirectResponse
    {
        $hasil = $request->session()->pull("impor-riwayat.{$jenis}");
        if (! $hasil) {
            return redirect()->route('riwayat-impor.create', $jenis)->withErrors(['berkas' => 'Unggah berkas lagi sebelum menyimpan.']);
        }
        if (collect($hasil)->contains(fn ($b) => $b['galat'])) {
            return redirect()->route('riwayat-impor.create', $jenis)->withErrors(['berkas' => 'Masih ada baris bergalat. Perbaiki berkas lalu unggah lagi.']);
        }

        $jumlah = $this->impor($request, $jenis)->simpan($hasil, $request->boolean('sertakan_ganda'));
        $dilewati = count($hasil) - $jumlah;

        return redirect()->route('riwayat-dosen.index', [$jenis, 'status' => 'semua'])
            ->with('status', "{$jumlah} kegiatan berhasil diimpor".($dilewati ? ", {$dilewati} baris yang mirip data lain dilewati." : '.'));
    }

    private function impor(Request $request, string $jenis): ImporRiwayat
    {
        return new ImporRiwayat(AlurRiwayat::JENIS[$jenis], $request->user());
    }
}
