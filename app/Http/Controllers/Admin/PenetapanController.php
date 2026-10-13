<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusProposal;
use App\Http\Controllers\Controller;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Services\AlurPenetapan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Ranking, the funding decision and the SK, one call at a time.
 */
class PenetapanController extends Controller
{
    public function index(): View
    {
        return view('admin.penetapan.index', [
            'periode' => PeriodeHibah::with('skema')
                ->withCount([
                    'proposal as menunggu_count' => fn ($q) => $q->where('status', StatusProposal::Ditinjau),
                    'proposal as disetujui_count' => fn ($q) => $q->where('status', StatusProposal::Disetujui),
                ])
                ->orderByDesc('tahun_anggaran')
                ->get()
                ->filter(fn (PeriodeHibah $p) => $p->menunggu_count > 0 || $p->disetujui_count > 0),
        ]);
    }

    public function show(PeriodeHibah $periode, AlurPenetapan $penetapan): View
    {
        return view('admin.penetapan.show', [
            'periode' => $periode->load('skema'),
            'peringkat' => $penetapan->peringkat($periode),
            'disetujui' => Proposal::where('periode_id', $periode->id)
                ->where('status', StatusProposal::Disetujui)
                ->with('ketua')
                ->orderBy('peringkat')
                ->get(),
        ]);
    }

    public function store(Request $request, PeriodeHibah $periode, AlurPenetapan $penetapan): RedirectResponse
    {
        $data = $request->validate([
            'didanai' => ['array'],
            'didanai.*' => ['integer'],
            'dana' => ['array'],
        ]);
        $dana = $data['dana'] ?? [];
        $pilihan = collect($data['didanai'] ?? [])->mapWithKeys(fn ($id) => [$id => $dana[$id] ?? null])->all();

        $penetapan->tetapkan($periode, $pilihan, $request->user());

        return back()->with('status', count($pilihan).' proposal ditetapkan didanai. Ketua sudah diberi tahu.');
    }

    public function sk(Request $request, PeriodeHibah $periode, AlurPenetapan $penetapan): RedirectResponse
    {
        $data = $request->validate([
            'no_sk' => ['required', 'string', 'max:255'],
            'tgl_sk' => ['required', 'date'],
        ]);

        $jumlah = $penetapan->terbitkanSk($periode, $data['no_sk'], Carbon::parse($data['tgl_sk']));

        return back()->with('status', "SK dicatat untuk {$jumlah} proposal.");
    }
}
