<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusProposal;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\AlurProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * LPPM's administrative screening of endorsed proposals.
 */
class SeleksiController extends Controller
{
    public function index(): View
    {
        return view('admin.seleksi.index', [
            'menunggu' => Proposal::where('status', StatusProposal::Diajukan)
                ->with(['periode.skema', 'ketua'])
                ->oldest('diajukan_at')
                ->get(),
        ]);
    }

    public function lolos(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        $alur->lolosAdministrasi($proposal, $request->user(), $request->input('catatan'));

        return back()->with('status', 'Proposal lolos seleksi administrasi.');
    }

    public function kembalikan(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:2000'],
            'batas_perbaikan' => ['required', 'date', 'after:today'],
        ]);

        $alur->kembalikan($proposal, $request->user(), $data['catatan'], Carbon::parse($data['batas_perbaikan'])->endOfDay());

        return back()->with('status', 'Proposal dikembalikan untuk diperbaiki.');
    }

    public function tolak(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:2000']]);

        $alur->tolak($proposal, $request->user(), $data['catatan']);

        return back()->with('status', 'Proposal ditolak.');
    }
}
