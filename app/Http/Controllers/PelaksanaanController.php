<?php

namespace App\Http\Controllers;

use App\Models\Berkas;
use App\Models\Laporan;
use App\Models\Logbook;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\TemplateDokumen;
use App\Services\AlurPelaksanaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The logbook and progress report of a funded proposal. Everyone who can open
 * the proposal can read them; the team writes.
 */
class PelaksanaanController extends Controller
{
    public function show(Request $request, Proposal $proposal, AlurPelaksanaan $alur): View
    {
        Gate::authorize('view', $proposal);
        $proposal->load(['periode', 'logbook.pencatat', 'logbook.berkas', 'laporan.berkas',
            'penilaian' => fn ($q) => $q->where('tahap', Penilaian::MONEV)->where('status', Penilaian::SELESAI)]);

        return view('proposal.pelaksanaan', [
            'proposal' => $proposal,
            'bisaMencatat' => $alur->bisaMencatatLogbook($proposal) && $alur->anggotaTim($proposal, $request->user()),
            'laporanKemajuan' => $proposal->laporan->where('jenis', Laporan::KEMAJUAN)->sortByDesc('diajukan_at')->first(),
            'templat' => TemplateDokumen::berlakuUntuk($proposal->periode->skema_id, $proposal->periode->tahun_anggaran)
                ->whereIn('jenis', [TemplateDokumen::LAPORAN_KEMAJUAN, TemplateDokumen::LAPORAN_KEUANGAN, TemplateDokumen::LOGBOOK])
                ->whereHas('berkasTerbaru')->get(),
        ]);
    }

    public function storeLogbook(Request $request, Proposal $proposal, AlurPelaksanaan $alur): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'kegiatan' => ['required', 'string', 'max:5000'],
            'persentase_progres' => ['nullable', 'integer', 'between:0,100'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $alur->catatLogbook($proposal, $data, $request->file('bukti'), $request->user());

        return back()->with('status', 'Entri logbook tersimpan.');
    }

    public function destroyLogbook(Request $request, Proposal $proposal, Logbook $logbook, AlurPelaksanaan $alur): RedirectResponse
    {
        abort_unless($logbook->proposal_id === $proposal->id, 404);

        $alur->hapusLogbook($logbook, $request->user());

        return back()->with('status', 'Entri logbook dihapus.');
    }

    public function storeLaporanKemajuan(Request $request, Proposal $proposal, AlurPelaksanaan $alur): RedirectResponse
    {
        $data = $request->validate([
            'ringkasan' => ['required', 'string', 'max:5000'],
            'persentase_capaian' => ['required', 'integer', 'between:0,100'],
            'persentase_anggaran_terpakai' => ['required', 'integer', 'between:0,100'],
            'laporan' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'keuangan' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $alur->kirimLaporanKemajuan($proposal, $data, $request->file('laporan'), $request->file('keuangan'), $request->user());

        return back()->with('status', 'Laporan kemajuan terkirim. LPPM akan menugaskan reviewer monev.');
    }

    /**
     * A file of this proposal's logbook or reports.
     */
    public function berkas(Proposal $proposal, Berkas $berkas): StreamedResponse
    {
        Gate::authorize('view', $proposal);
        $pemilik = $berkas->pemilik;
        abort_unless(($pemilik instanceof Logbook || $pemilik instanceof Laporan) && $pemilik->proposal_id === $proposal->id, 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }
}
