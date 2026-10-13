<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\Penilaian;
use App\Models\Proposal;
use Illuminate\View\View;

/**
 * What the operator chases: quiet logbooks, late progress reports, and monev
 * still without a result.
 */
class PemantauanController extends Controller
{
    /** Days without a logbook entry before a running grant is flagged. */
    public const HARI_LOGBOOK = 14;

    public function index(): View
    {
        $batasLogbook = today()->subDays(self::HARI_LOGBOOK);

        return view('pemantauan.index', [
            'hariLogbook' => self::HARI_LOGBOOK,
            'logbookSepi' => Proposal::where('status', StatusProposal::Berjalan)
                ->whereDoesntHave('logbook', fn ($q) => $q->whereDate('tanggal', '>=', $batasLogbook))
                ->with('periode', 'ketua')
                ->withMax('logbook', 'tanggal')
                ->orderBy('periode_id')
                ->get(),
            'laporanTerlambat' => Proposal::where('status', StatusProposal::Berjalan)
                ->whereHas('periode', fn ($q) => $q->whereDate('tgl_laporan_kemajuan', '<', today()))
                ->with('periode', 'ketua')
                ->orderBy('periode_id')
                ->get(),
            'monevTertunda' => Proposal::where('status', StatusProposal::Dimonev)
                ->whereDoesntHave('penilaian', fn ($q) => $q->where('tahap', Penilaian::MONEV)->where('status', Penilaian::SELESAI))
                ->with(['periode', 'ketua', 'penilaian' => fn ($q) => $q->where('tahap', Penilaian::MONEV)->with('reviewer')])
                ->orderBy('periode_id')
                ->get(),
        ]);
    }
}
