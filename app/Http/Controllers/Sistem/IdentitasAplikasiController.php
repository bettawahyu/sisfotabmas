<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Services\IdentitasAplikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The app's name, logo, favicon and landing page, kept by the system admin.
 */
class IdentitasAplikasiController extends Controller
{
    public function __construct(private IdentitasAplikasi $identitas) {}

    public function edit(): View
    {
        return view('sistem.identitas.edit', ['identitas' => $this->identitas]);
    }

    public function update(Request $request): RedirectResponse
    {
        // SVG is refused: it can carry scripts. The built-in SVG logo stays the default.
        $gambar = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp'];
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'nama_singkat' => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'logo' => [...$gambar, 'max:1024'],
            'logo_gelap' => [...$gambar, 'max:1024'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'latar' => [...$gambar, 'max:3072'],
        ], [], [
            'nama_singkat' => 'nama singkat',
            'logo_gelap' => 'logo untuk latar gelap',
            'latar' => 'gambar latar landing',
        ]);

        $this->identitas->simpan($data, [
            'logo' => $request->file('logo'),
            'logo-gelap' => $request->file('logo_gelap'),
            'favicon' => $request->file('favicon'),
            'latar' => $request->file('latar'),
        ], $request->user());

        return redirect()->route('sistem.identitas.edit')->with('status', 'Identitas aplikasi disimpan.');
    }

    public function hapusBerkas(Request $request, string $jenis): RedirectResponse
    {
        $this->identitas->hapusBerkas($jenis, $request->user());

        return redirect()->route('sistem.identitas.edit')->with('status', 'Gambar dikembalikan ke bawaan.');
    }

    /**
     * Serve an uploaded logo, favicon or background to everyone, the landing page included.
     */
    public function berkas(string $jenis): StreamedResponse
    {
        $path = $this->identitas->berkas($jenis);
        abort_if($path === null || ! Storage::exists($path), 404);

        return Storage::response($path, headers: [
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
