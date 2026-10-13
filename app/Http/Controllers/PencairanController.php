<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposal;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Role;
use App\Services\AlurPencairan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Finance records tranches; LPPM admins see the same page without the forms.
 */
class PencairanController extends Controller
{
    public function index(Request $request, AlurPencairan $alur): View
    {
        return view('pencairan.index', [
            'menunggu' => Proposal::where('status', StatusProposal::Didanai)
                ->whereDoesntHave('pencairan', fn ($q) => $q->where('termin_ke', 1))
                ->with('periode', 'ketua')
                ->oldest('tgl_kontrak')
                ->get()
                ->each(fn (Proposal $p) => $p->setAttribute('jumlah_termin_1', $alur->jumlahTermin($p, 1))),
            'riwayat' => PencairanDana::with('proposal.ketua', 'pencair', 'bukti')->latest('tgl_cair')->latest('id')->paginate(30),
            'bisaCatat' => $request->user()->hasRole(Role::KEUANGAN),
        ]);
    }

    public function store(Request $request, Proposal $proposal, AlurPencairan $alur): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::KEUANGAN), 403);
        $data = $request->validate([
            'tgl_cair' => ['required', 'date'],
            'pajak_lunas' => ['boolean'],
            'bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $alur->cairkanTermin1($proposal, Carbon::parse($data['tgl_cair']), $request->boolean('pajak_lunas'), $request->file('bukti'), $request->user());

        return back()->with('status', 'Termin 1 dicatat. Proposal kini berjalan.');
    }

    public function bukti(PencairanDana $pencairan): StreamedResponse
    {
        $berkas = $pencairan->bukti;
        abort_if($berkas === null, 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }
}
