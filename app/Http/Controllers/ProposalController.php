<?php

namespace App\Http\Controllers;

use App\Enums\TipeAnggota;
use App\Models\BidangFokus;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Services\AlurProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProposalController extends Controller
{
    /**
     * The lecturer's own proposals, as lead or member, plus open calls.
     */
    public function index(Request $request): View
    {
        $dosen = $request->user()->dosen;

        return view('proposal.index', [
            'dosen' => $dosen,
            'periodeDibuka' => PeriodeHibah::sedangDibuka()->with('skema')->orderBy('tgl_tutup')->get(),
            'sebagaiKetua' => $dosen?->proposalSebagaiKetua()->with('periode.skema')->latest()->get() ?? collect(),
            'sebagaiAnggota' => $dosen
                ? Proposal::whereHas('anggota', fn ($q) => $q->where('tipe', TipeAnggota::Dosen)->where('dosen_id', $dosen->id))
                    ->with(['periode.skema', 'ketua', 'anggota' => fn ($q) => $q->where('dosen_id', $dosen->id)])
                    ->latest()->get()
                : collect(),
        ]);
    }

    public function create(Request $request, PeriodeHibah $periode): View|RedirectResponse
    {
        if ($redirect = $this->butuhProfil($request)) {
            return $redirect;
        }
        abort_unless($periode->menerimaPengajuan(), 404);

        return view('proposal.create', [
            'periode' => $periode->load('skema'),
            'bidangFokus' => $this->bidangFokus($periode),
        ]);
    }

    public function store(Request $request, PeriodeHibah $periode): RedirectResponse
    {
        if ($redirect = $this->butuhProfil($request)) {
            return $redirect;
        }
        abort_unless($periode->menerimaPengajuan(), 404);

        $data = $this->validasiIdentitas($request, $periode);
        $data['lama_tahun'] = $periode->skema->is_multitahun ? ($data['lama_tahun'] ?? 1) : 1;

        $proposal = $periode->proposal()->create($data + ['ketua_id' => $request->user()->dosen->id]);

        return redirect()->route('proposal.show', $proposal)->with('status', 'Draf proposal dibuat. Lengkapi anggota, RAB, luaran, dan substansi.');
    }

    public function show(Request $request, Proposal $proposal, AlurProposal $alur): View
    {
        Gate::authorize('view', $proposal);

        $proposal->load(['periode.skema', 'ketua', 'bidangFokus', 'anggota.dosen', 'mitra', 'rab', 'luaran', 'berkasSubstansi', 'riwayatStatus.pelaku']);

        return view('proposal.show', [
            'proposal' => $proposal,
            'bisaDiubah' => Gate::allows('update', $proposal),
            'kekurangan' => Gate::allows('update', $proposal) ? $alur->kekurangan($proposal) : [],
            'bidangFokus' => $this->bidangFokus($proposal->periode),
        ]);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $data = $this->validasiIdentitas($request, $proposal->periode);
        $data['lama_tahun'] = $proposal->periode->skema->is_multitahun ? ($data['lama_tahun'] ?? 1) : 1;
        $proposal->update($data);

        return back()->with('status', 'Identitas proposal tersimpan.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        Gate::authorize('delete', $proposal);

        $proposal->delete();

        return redirect()->route('proposal.index')->with('status', 'Draf proposal dihapus.');
    }

    public function kirim(Request $request, Proposal $proposal, AlurProposal $alur): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $alur->kirim($proposal, $request->user());

        return back()->with('status', 'Proposal terkirim.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasiIdentitas(Request $request, PeriodeHibah $periode): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:500'],
            'ringkasan' => ['required', 'string', 'max:5000'],
            'kata_kunci' => ['nullable', 'string', 'max:255'],
            'rumpun_ilmu' => ['nullable', 'string', 'max:255'],
            'bidang_fokus_id' => ['nullable', 'exists:bidang_fokus,id'],
            'tkt_awal' => ['nullable', 'integer', 'between:1,9'],
            'tkt_target' => ['nullable', 'integer', 'between:1,9', 'gte:tkt_awal'],
            'lama_tahun' => ['nullable', 'integer', 'between:1,'.$periode->skema->lama_maks_tahun],
        ]);
    }

    private function bidangFokus(PeriodeHibah $periode)
    {
        return BidangFokus::where('jenis_kegiatan', $periode->skema->jenis_kegiatan)->where('is_active', true)->orderBy('nama')->get();
    }

    private function butuhProfil(Request $request): ?RedirectResponse
    {
        return $request->user()->dosen
            ? null
            : redirect()->route('profil-dosen.edit')->with('status', 'Lengkapi profil dosen sebelum membuat proposal.');
    }
}
