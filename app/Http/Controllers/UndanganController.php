<?php

namespace App\Http\Controllers;

use App\Models\ProposalAnggota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A lecturer invited to a proposal team confirms or declines.
 */
class UndanganController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $anggota = $this->undanganMilik($request, $token);

        return view('proposal.undangan', ['anggota' => $anggota->load('proposal.ketua', 'proposal.periode.skema')]);
    }

    public function jawab(Request $request, string $token): RedirectResponse
    {
        $anggota = $this->undanganMilik($request, $token);
        abort_unless($anggota->proposal->bisaDiubah(), 403);

        $bersedia = $request->validate(['jawaban' => ['required', 'in:terima,tolak']])['jawaban'] === 'terima';
        $anggota->forceFill([
            'dikonfirmasi_at' => $bersedia ? now() : null,
            'ditolak_at' => $bersedia ? null : now(),
        ])->save();

        return redirect()->route('proposal.index')
            ->with('status', $bersedia ? 'Anda bergabung di tim proposal.' : 'Undangan ditolak.');
    }

    private function undanganMilik(Request $request, string $token): ProposalAnggota
    {
        $anggota = ProposalAnggota::where('token_undangan', $token)->firstOrFail();
        abort_unless($request->user()->dosen?->id === $anggota->dosen_id, 403);

        return $anggota;
    }
}
