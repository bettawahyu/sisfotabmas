<?php

namespace App\Http\Controllers\Riwayat;

use App\Http\Controllers\Controller;
use App\Models\KabupatenKota;
use App\Models\Mitra;
use App\Services\AlurRiwayat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The partner register LPPM keeps. A lecturer adds a new partner from the
 * record form; LPPM then decides whether it joins the register.
 */
class MitraController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Mitra::STATUS))],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);
        // Open on the proposals waiting for a decision when there are any.
        $filter['status'] ??= Mitra::where('status', Mitra::USULAN)->exists() ? Mitra::USULAN : Mitra::MASTER;

        return view('riwayat.mitra.index', [
            'filter' => $filter,
            'jumlah' => Mitra::selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
            'mitra' => Mitra::where('status', $filter['status'])
                ->when($filter['cari'] ?? null, fn ($q, $cari) => $q->where('nama', 'like', "%{$cari}%"))
                ->with(['kabupatenKota.provinsi', 'pengusul'])
                ->withCount(['riwayatKegiatan', 'proposalMitra'])
                ->orderBy('nama')->paginate(25)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Mitra(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $mitra = new Mitra($this->validasi($request) + ['is_active' => $request->boolean('is_active')]);
        $mitra->forceFill(['status' => Mitra::MASTER, 'diusulkan_oleh' => $request->user()->id,
            'diverifikasi_oleh' => $request->user()->id, 'diverifikasi_at' => now()])->save();

        return redirect()->route('mitra.index')->with('status', 'Mitra ditambahkan ke data master.');
    }

    public function edit(Mitra $mitra): View
    {
        return $this->form($mitra);
    }

    public function update(Request $request, Mitra $mitra): RedirectResponse
    {
        $mitra->update($this->validasi($request) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('mitra.index', ['status' => $mitra->status])->with('status', 'Mitra diperbarui.');
    }

    /**
     * Takes a lecturer's proposed partner into the register, or keeps it out;
     * either way the records that use it keep it.
     */
    public function verifikasi(Request $request, Mitra $mitra): RedirectResponse
    {
        $data = $request->validate(['aksi' => ['required', 'in:terima,tolak']]);
        abort_unless($mitra->status === Mitra::USULAN, 422, 'Mitra ini tidak sedang diusulkan.');

        $mitra->forceFill([
            'status' => $data['aksi'] === 'terima' ? Mitra::MASTER : Mitra::DITOLAK,
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at' => now(),
        ])->save();

        return back()->with('status', $data['aksi'] === 'terima' ? "{$mitra->nama} masuk data master." : "{$mitra->nama} tidak dimasukkan ke data master.");
    }

    /**
     * The new-partner modal on the record form. LPPM's go straight into the
     * register; a lecturer's wait for LPPM but can be used at once.
     */
    public function usulan(Request $request): JsonResponse
    {
        abort_unless(AlurRiwayat::petugasLppm($request->user()) || $request->user()->dosen !== null, 403);

        $mitra = Mitra::usulkan($this->validasi($request), $request->user(), AlurRiwayat::petugasLppm($request->user()));

        return response()->json(['id' => $mitra->id, 'nama' => $mitra->nama, 'bidang' => $mitra->bidang, 'status' => $mitra->status], $mitra->wasRecentlyCreated ? 201 : 200);
    }

    private function form(Mitra $mitra): View
    {
        return view('riwayat.mitra.form', ['mitra' => $mitra, 'wilayah' => KabupatenKota::pilihan()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'bidang' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota_kode' => ['nullable', 'exists:kabupaten_kota,kode'],
        ], [], ['bidang' => 'bidang usaha', 'kontak' => 'telepon atau email']);
    }
}
