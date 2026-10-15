<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\Luaran;
use App\Models\PencairanDana;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What the LPPM or institution head reads: how many proposals came in, how many
 * were funded, how much money went out, and what the grants produced. Read only.
 */
class PimpinanController extends Controller
{
    /** Statuses that mean the proposal won funding. */
    public const DIDANAI = [StatusProposal::Didanai, StatusProposal::Berjalan, StatusProposal::Dimonev, StatusProposal::Selesai];

    public function index(Request $request): View
    {
        $daftarTahun = PeriodeHibah::distinct()->orderByDesc('tahun_anggaran')->pluck('tahun_anggaran');
        $tahun = (int) $request->query('tahun', $daftarTahun->first());

        $proposal = Proposal::where('status', '!=', StatusProposal::Draft)
            ->whereHas('periode', fn ($q) => $q->where('tahun_anggaran', $tahun))
            ->with('periode.skema', 'ketua.prodi.fakultas')
            ->get();
        $didanai = $proposal->filter(fn (Proposal $p) => in_array($p->status, self::DIDANAI, true));
        $luaran = Luaran::with('jenisLuaran')->whereIn('proposal_id', $didanai->modelKeys())->get();

        return view('pimpinan.index', [
            'daftarTahun' => $daftarTahun,
            'tahun' => $tahun,
            'jumlahUsulan' => $proposal->count(),
            'jumlahDidanai' => $didanai->count(),
            'danaDisetujui' => $didanai->sum('dana_disetujui'),
            'danaDicairkan' => PencairanDana::whereIn('proposal_id', $didanai->modelKeys())->sum('jumlah'),
            'perStatus' => collect(StatusProposal::cases())
                ->reject(fn (StatusProposal $s) => $s === StatusProposal::Draft)
                ->map(fn (StatusProposal $s) => ['label' => $s->label(), 'jumlah' => $proposal->where('status', $s)->count()]),
            'perPeriode' => $proposal->groupBy('periode_id')->map(fn ($kelompok) => [
                'nama' => $kelompok->first()->periode->nama,
                'skema' => $kelompok->first()->periode->skema->nama,
                'usulan' => $kelompok->count(),
                'didanai' => $kelompok->intersect($didanai)->count(),
                'dana' => $kelompok->intersect($didanai)->sum('dana_disetujui'),
            ])->sortBy('nama')->values(),
            'perFakultas' => $proposal->groupBy(fn (Proposal $p) => $p->ketua?->prodi?->fakultas?->nama ?? 'Tanpa fakultas')
                ->map(fn ($kelompok, $nama) => [
                    'nama' => $nama,
                    'usulan' => $kelompok->count(),
                    'didanai' => $kelompok->intersect($didanai)->count(),
                ])->sortBy('nama')->values(),
            'perLuaran' => $luaran->groupBy(fn (Luaran $l) => $l->jenis)->map(fn ($kelompok) => [
                'label' => $kelompok->first()->namaJenis(),
                'target' => $kelompok->count(),
                'tercapai' => $kelompok->where('status_capaian', 'terbit')->count(),
            ])->sortBy('label')->values(),
        ]);
    }
}
