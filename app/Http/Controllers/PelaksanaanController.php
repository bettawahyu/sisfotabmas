<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\Berkas;
use App\Models\Laporan;
use App\Models\Logbook;
use App\Models\Luaran;
use App\Models\Proposal;
use App\Models\TemplateDokumen;
use App\Services\AlurKarya;
use App\Services\AlurPelaksanaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The logbook and progress report of a funded proposal. Everyone who can open
 * the proposal can read them; the team writes. A proposal only gets here once
 * its first tranche is paid.
 */
class PelaksanaanController extends Controller
{
    /**
     * The lecturer's activities whose first tranche is paid.
     */
    public function index(Request $request): View
    {
        $dosen = $request->user()->dosen;

        return view('pelaksanaan.index', [
            'daftar' => $dosen
                ? Proposal::dalamPelaksanaan()->timDosen($dosen)->with(['periode.skema', 'ketua'])->latest('updated_at')->get()
                : collect(),
            'dosen' => $dosen,
        ]);
    }

    public function show(Request $request, Proposal $proposal, AlurPelaksanaan $alur): View
    {
        Gate::authorize('view', $proposal);
        abort_unless($proposal->dalamPelaksanaan(), 404);
        $proposal->load(['periode', 'logbook.pencatat', 'logbook.berkas', 'laporan.berkas', 'luaran.publikasi', 'luaran.hki', 'luaran.paten']);
        $isKetua = $proposal->isKetua($request->user()->dosen);
        // What the lead can link each output to, keyed by model class.
        $karyaKetua = $isKetua ? collect(AlurKarya::JENIS)->mapWithKeys(fn ($model) => [
            $model => $model::milikDosen($request->user()->dosen)->where('status_verifikasi', '!=', $model::DITOLAK)->latest('updated_at')->get(),
        ]) : collect();
        $laporanAkhir = $alur->laporanAkhir($proposal);
        $hasilMonev = $alur->hasilMonev($proposal);

        return view('pelaksanaan.show', [
            'proposal' => $proposal,
            'isKetua' => $isKetua,
            'karyaKetua' => $karyaKetua,
            'bisaMencatat' => $alur->bisaMencatatLogbook($proposal) && $alur->anggotaTim($proposal, $request->user()),
            'laporanKemajuan' => $proposal->laporan->where('jenis', Laporan::KEMAJUAN)->sortByDesc('diajukan_at')->first(),
            'monev' => $hasilMonev,
            'laporanAkhir' => $laporanAkhir,
            'bisaKirimAkhir' => $isKetua && $hasilMonev !== null && $proposal->status === StatusProposal::Dimonev
                && ($laporanAkhir === null || $laporanAkhir->status === Laporan::DIKEMBALIKAN),
            'templat' => TemplateDokumen::berlakuUntuk($proposal->periode->skema_id, $proposal->periode->tahun_anggaran)
                ->whereIn('jenis', [TemplateDokumen::LAPORAN_KEMAJUAN, TemplateDokumen::LAPORAN_KEUANGAN, TemplateDokumen::LAPORAN_AKHIR, TemplateDokumen::LOGBOOK])
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

    public function updateLuaran(Request $request, Proposal $proposal, Luaran $luaran, AlurPelaksanaan $alur): RedirectResponse
    {
        abort_unless($luaran->proposal_id === $proposal->id, 404);
        $data = $request->validate([
            'status_capaian' => ['required', 'string', 'max:20'],
            'url_bukti' => ['nullable', 'url', 'max:2048'],
            'karya_id' => ['nullable', 'integer'],
        ]);

        $alur->laporCapaianLuaran($luaran, $data['status_capaian'], $data['url_bukti'] ?? null, $request->user(),
            isset($data['karya_id']) ? (int) $data['karya_id'] : null);

        return back()->with('status', 'Capaian luaran tersimpan dan menunggu validasi operator.');
    }

    public function storeLaporanAkhir(Request $request, Proposal $proposal, AlurPelaksanaan $alur): RedirectResponse
    {
        $data = $request->validate([
            'ringkasan' => ['required', 'string', 'max:5000'],
            'persentase_capaian' => ['required', 'integer', 'between:0,100'],
            'laporan' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'keuangan' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $alur->kirimLaporanAkhir($proposal, $data, $request->file('laporan'), $request->file('keuangan'), $request->user());

        return back()->with('status', 'Laporan akhir terkirim dan menunggu verifikasi operator.');
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
