<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusProposal;
use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Services\AlurReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * LPPM gives each progress report one desk reviewer and reads the monev result.
 */
class MonevController extends Controller
{
    public function index(): View
    {
        return view('admin.monev.index', [
            'proposal' => Proposal::where('status', StatusProposal::Dimonev)
                ->with(['periode', 'ketua',
                    'laporan' => fn ($q) => $q->where('jenis', Laporan::KEMAJUAN)->latest('diajukan_at')->with('berkas'),
                    'penilaian' => fn ($q) => $q->where('tahap', Penilaian::MONEV)->with('reviewer')])
                ->orderBy('periode_id')
                ->get(),
            'reviewer' => User::whereHas('roles', fn ($q) => $q->where('kode', Role::REVIEWER))->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Proposal $proposal, AlurReview $review): RedirectResponse
    {
        $data = $request->validate(['reviewer_id' => ['required', 'exists:users,id']]);
        $reviewer = User::findOrFail($data['reviewer_id']);

        $review->tugaskanMonev($proposal, $reviewer, $request->user());

        return back()->with('status', "{$reviewer->name} ditugaskan sebagai reviewer monev.");
    }

    public function destroy(Proposal $proposal, Penilaian $penilaian, AlurReview $review): RedirectResponse
    {
        abort_unless($penilaian->proposal_id === $proposal->id && $penilaian->tahap === Penilaian::MONEV, 404);
        $review->batalkan($penilaian);

        return back()->with('status', 'Penugasan reviewer monev dibatalkan.');
    }

    public function bukaKembali(Request $request, Proposal $proposal, Penilaian $penilaian, AlurReview $review): RedirectResponse
    {
        abort_unless($penilaian->proposal_id === $proposal->id && $penilaian->tahap === Penilaian::MONEV, 404);
        $data = $request->validate(['alasan' => ['required', 'string', 'max:1000']]);

        $review->bukaKembali($penilaian, $data['alasan']);

        return back()->with('status', 'Hasil monev dibuka kembali untuk reviewer.');
    }
}
