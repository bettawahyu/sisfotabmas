<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Announcements as their readers see them.
 */
class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        return view('pengumuman.index', [
            'pengumuman' => Pengumuman::tampil()->untuk($request->user())->with('periode')->paginate(15),
        ]);
    }

    public function show(Request $request, Pengumuman $pengumuman): View
    {
        $this->pastikanBolehBaca($request, $pengumuman);

        return view('pengumuman.show', ['pengumuman' => $pengumuman->load(['periode', 'lampiran'])]);
    }

    public function lampiran(Request $request, Pengumuman $pengumuman): StreamedResponse
    {
        $this->pastikanBolehBaca($request, $pengumuman);
        $berkas = $pengumuman->lampiran;
        abort_if($berkas === null, 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }

    /**
     * Readers see live announcements meant for them; whoever keeps announcements
     * may also preview scheduled and expired ones.
     */
    private function pastikanBolehBaca(Request $request, Pengumuman $pengumuman): void
    {
        $user = $request->user();
        if ($user->can('kelola-pengumuman')) {
            return;
        }

        abort_unless($pengumuman->sedangTampil() && Pengumuman::whereKey($pengumuman->id)->untuk($user)->exists(), 404);
    }
}
