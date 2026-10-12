<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use App\Services\AlurReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A reviewer's tasks. The proposal is shown without the team's names (blind review).
 */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $selesai = $request->query('tab') === 'selesai';

        return view('review.index', [
            'tab' => $selesai ? 'selesai' : 'menunggu',
            'tugas' => $request->user()->penilaian()
                ->where('status', $selesai ? Penilaian::SELESAI : Penilaian::DITUGASKAN)
                ->with('proposal.periode.skema')
                ->when($selesai, fn ($q) => $q->latest('dinilai_at'), fn ($q) => $q->oldest())
                ->paginate(20)
                ->withQueryString(),
            'jumlahMenunggu' => $request->user()->penilaian()->where('status', Penilaian::DITUGASKAN)->count(),
        ]);
    }

    public function show(Request $request, Penilaian $penilaian, AlurReview $review): View
    {
        $this->pastikanMilik($request, $penilaian);
        $penilaian->load('nilai', 'proposal.periode.skema', 'proposal.bidangFokus', 'proposal.cabangIlmu.induk.induk',
            'proposal.rab', 'proposal.luaran', 'proposal.mitra', 'proposal.berkasSubstansi');

        return view('review.show', [
            'penilaian' => $penilaian,
            'proposal' => $penilaian->proposal,
            'kriteria' => $review->kriteria($penilaian->proposal),
        ]);
    }

    public function update(Request $request, Penilaian $penilaian, AlurReview $review): RedirectResponse
    {
        $this->pastikanMilik($request, $penilaian);
        $data = $request->validate([
            'skor' => ['array'],
            'rekomendasi' => ['nullable', Rule::in(array_keys(Penilaian::REKOMENDASI))],
            'komentar_untuk_pengusul' => ['nullable', 'string', 'max:5000'],
            'catatan_internal' => ['nullable', 'string', 'max:5000'],
            'rekomendasi_dana' => ['nullable', 'integer', 'min:0'],
        ]);
        $kirim = $request->input('aksi') === 'kirim';

        $review->simpan($penilaian, $data['skor'] ?? [], $data, $kirim);

        return $kirim
            ? redirect()->route('review.index')->with('status', 'Penilaian dikirim ke LPPM.')
            : back()->with('status', 'Draf penilaian tersimpan.');
    }

    public function substansi(Request $request, Penilaian $penilaian): StreamedResponse
    {
        $this->pastikanMilik($request, $penilaian);
        $berkas = $penilaian->proposal->berkasSubstansi;
        abort_if($berkas === null, 404);

        return Storage::download($berkas->path, 'substansi-proposal-'.$penilaian->proposal_id.'.pdf');
    }

    private function pastikanMilik(Request $request, Penilaian $penilaian): void
    {
        abort_unless($penilaian->reviewer_id === $request->user()->id, 403);
    }
}
