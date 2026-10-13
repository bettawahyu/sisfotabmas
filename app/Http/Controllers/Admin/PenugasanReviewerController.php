<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusProposal;
use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Services\AlurReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * LPPM assigns two reviewers to each proposal under review and follows their scores.
 */
class PenugasanReviewerController extends Controller
{
    public function index(): View
    {
        return view('admin.review.index', [
            'proposal' => Proposal::where('status', StatusProposal::Ditinjau)
                ->with(['periode.skema', 'ketua', 'penilaian' => fn ($q) => $q->where('tahap', Penilaian::SELEKSI)->with('reviewer')])
                ->orderBy('periode_id')
                ->oldest('diajukan_at')
                ->get(),
        ]);
    }

    public function show(Proposal $proposal, AlurReview $review): View
    {
        $proposal->load(['periode.skema', 'ketua.prodi', 'penilaian' => fn ($q) => $q->where('tahap', Penilaian::SELEKSI)->with('reviewer', 'nilai')]);
        $reviewer = User::whereHas('roles', fn ($q) => $q->where('kode', Role::REVIEWER))->with('dosen.prodi')->orderBy('name')->get();

        return view('admin.review.show', [
            'proposal' => $proposal,
            'kriteria' => $review->kriteria($proposal),
            'slot' => $proposal->penilaian->keyBy('urutan_reviewer'),
            'reviewer' => $reviewer,
            'peringatan' => $reviewer->mapWithKeys(fn (User $u) => [$u->id => $review->peringatan($proposal, $u)])->filter(),
        ]);
    }

    public function store(Request $request, Proposal $proposal, AlurReview $review): RedirectResponse
    {
        $data = $request->validate([
            'urutan' => ['required', 'integer', 'between:1,'.Penilaian::JUMLAH_REVIEWER],
            'reviewer_id' => ['required', 'exists:users,id'],
        ]);
        $reviewer = User::findOrFail($data['reviewer_id']);

        $review->tugaskan($proposal, $reviewer, (int) $data['urutan'], $request->user());

        $pesan = "{$reviewer->name} ditugaskan sebagai reviewer {$data['urutan']}.";
        if ($peringatan = $review->peringatan($proposal, $reviewer)) {
            $pesan .= ' Perhatian: '.$peringatan;
        }

        return back()->with('status', $pesan);
    }

    public function destroy(Proposal $proposal, Penilaian $penilaian, AlurReview $review): RedirectResponse
    {
        abort_unless($penilaian->proposal_id === $proposal->id, 404);
        $review->batalkan($penilaian);

        return back()->with('status', 'Penugasan reviewer dibatalkan.');
    }

    public function bukaKembali(Request $request, Proposal $proposal, Penilaian $penilaian, AlurReview $review): RedirectResponse
    {
        abort_unless($penilaian->proposal_id === $proposal->id, 404);
        $data = $request->validate(['alasan' => ['required', 'string', 'max:1000']]);

        $review->bukaKembali($penilaian, $data['alasan']);

        return back()->with('status', 'Penilaian dibuka kembali untuk reviewer.');
    }
}
