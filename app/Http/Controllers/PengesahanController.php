<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\PengesahanProposal;
use App\Models\Proposal;
use App\Services\AlurProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Faculty and programme heads endorse proposals (lembar pengesahan).
 */
class PengesahanController extends Controller
{
    public function index(Request $request): View
    {
        return view('pengesahan.index', [
            'menunggu' => Proposal::where('status', StatusProposal::Draft)
                ->whereNotNull('dikirim_at')
                ->dalamUnit($request->user())
                ->with(['periode.skema', 'ketua.prodi'])
                ->oldest('dikirim_at')
                ->get(),
            'riwayat' => PengesahanProposal::where('oleh', $request->user()->id)
                ->with('proposal.periode.skema', 'proposal.ketua')
                ->latest('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function sahkan(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        $alur->sahkan($proposal, $request->user());

        return redirect()->route('pengesahan.index')->with('status', 'Proposal disahkan dan diteruskan ke LPPM.');
    }

    public function tolak(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:2000']]);

        $alur->tolakPengesahan($proposal, $request->user(), $data['catatan']);

        return redirect()->route('pengesahan.index')->with('status', 'Proposal dikembalikan ke ketua.');
    }
}
