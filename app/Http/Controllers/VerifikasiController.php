<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\Laporan;
use App\Models\Luaran;
use App\Services\AlurPelaksanaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The operator checks final reports and the outputs leads report.
 */
class VerifikasiController extends Controller
{
    public function index(): View
    {
        return view('verifikasi.index', [
            'laporan' => Laporan::where('jenis', Laporan::AKHIR)->where('status', Laporan::DIAJUKAN)
                ->with('proposal.ketua', 'proposal.periode', 'berkas')
                ->oldest('diajukan_at')
                ->get(),
            'luaran' => Luaran::where('status_validasi', Luaran::MENUNGGU)->where('status_capaian', '!=', Luaran::DIRENCANAKAN)
                ->whereHas('proposal', fn ($q) => $q->whereIn('status', [StatusProposal::Berjalan, StatusProposal::Dimonev]))
                ->with('proposal.ketua')
                ->oldest('updated_at')
                ->get(),
        ]);
    }

    public function laporan(Request $request, Laporan $laporan, AlurPelaksanaan $alur): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:terima,kembalikan'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $alur->verifikasiLaporanAkhir($laporan, $data['aksi'] === 'terima', $data['catatan'] ?? null, $request->user());

        return back()->with('status', $data['aksi'] === 'terima' ? 'Laporan akhir diverifikasi.' : 'Laporan akhir dikembalikan ke ketua.');
    }

    public function luaran(Request $request, Luaran $luaran, AlurPelaksanaan $alur): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', 'in:valid,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $alur->validasiLuaran($luaran, $data['aksi'] === 'valid', $data['catatan'] ?? null, $request->user());

        return back()->with('status', $data['aksi'] === 'valid' ? 'Luaran divalidasi.' : 'Luaran ditolak dan ketua diberi tahu.');
    }
}
