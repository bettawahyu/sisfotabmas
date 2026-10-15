<?php

namespace App\Http\Controllers\Sistem;

use App\Enums\JenisKegiatan;
use App\Http\Controllers\Controller;
use App\Models\BidangFokus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Research and community-service focus areas offered on the proposal form.
 */
class BidangFokusController extends Controller
{
    public function index(): View
    {
        return view('sistem.bidang-fokus.index', [
            'bidang' => BidangFokus::with('induk')->withCount('proposal')->orderBy('jenis_kegiatan')->orderBy('kode')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new BidangFokus(['jenis_kegiatan' => JenisKegiatan::Penelitian, 'is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        BidangFokus::create($this->validasi($request));

        return redirect()->route('sistem.bidang-fokus.index')->with('status', 'Bidang fokus ditambahkan.');
    }

    public function edit(BidangFokus $bidangFokus): View
    {
        return $this->form($bidangFokus);
    }

    public function update(Request $request, BidangFokus $bidangFokus): RedirectResponse
    {
        $bidangFokus->update($this->validasi($request, $bidangFokus));

        return redirect()->route('sistem.bidang-fokus.index')->with('status', 'Bidang fokus diperbarui.');
    }

    private function form(BidangFokus $bidangFokus): View
    {
        return view('sistem.bidang-fokus.form', [
            'bidangFokus' => $bidangFokus,
            'calonInduk' => BidangFokus::whereNull('induk_id')->whereKeyNot($bidangFokus->id)
                ->aktifAtau($bidangFokus->induk_id)->orderBy('kode')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?BidangFokus $bidangFokus = null): array
    {
        $data = $request->validate([
            'jenis_kegiatan' => ['required', Rule::enum(JenisKegiatan::class)],
            'kode' => ['required', 'string', 'max:30', Rule::unique('bidang_fokus')->ignore($bidangFokus)],
            'nama' => ['required', 'string', 'max:255'],
            'induk_id' => ['nullable', 'integer', 'exists:bidang_fokus,id'],
        ]);

        // One level of sub-areas, within the same kind of activity.
        $induk = BidangFokus::find($data['induk_id'] ?? null);
        if ($induk && ($induk->induk_id || $induk->is($bidangFokus) || $induk->jenis_kegiatan->value !== $data['jenis_kegiatan'])) {
            throw ValidationException::withMessages(['induk_id' => 'Induk harus bidang fokus utama dengan jenis kegiatan yang sama.']);
        }

        return ['induk_id' => $induk?->id, 'is_active' => $request->boolean('is_active')] + $data;
    }
}
