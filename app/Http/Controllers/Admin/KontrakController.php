<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusProposal;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\AlurPencairan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * LPPM records the contract of each approved proposal.
 */
class KontrakController extends Controller
{
    public function index(): View
    {
        return view('admin.kontrak.index', [
            'proposal' => Proposal::where('status', StatusProposal::Disetujui)
                ->with('periode', 'ketua')
                ->orderBy('periode_id')
                ->orderBy('peringkat')
                ->get(),
        ]);
    }

    public function store(Request $request, Proposal $proposal, AlurPencairan $pencairan): RedirectResponse
    {
        $data = $request->validate([
            'no_kontrak' => ['required', 'string', 'max:255'],
            'tgl_kontrak' => ['required', 'date'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after:tgl_mulai'],
        ]);

        $pencairan->catatKontrak($proposal, $data, $request->user());

        return back()->with('status', 'Kontrak dicatat. Keuangan diberi tahu untuk mencairkan termin 1.');
    }
}
