<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\RumpunIlmu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The three-level science classification (rumpun › pohon › cabang); proposals pick a cabang.
 */
class RumpunIlmuController extends Controller
{
    public function index(): View
    {
        $semua = RumpunIlmu::withCount(['turunan', 'proposal'])->orderBy('kode')->get();

        return view('sistem.rumpun-ilmu.index', [
            'rumpun' => $semua->where('tingkat', RumpunIlmu::RUMPUN)->values(),
            'turunan' => $semua->groupBy('induk_id'),
        ]);
    }

    public function create(Request $request): View
    {
        $induk = RumpunIlmu::find($request->integer('induk'));
        $tingkat = $induk ? array_search($induk->tingkat, RumpunIlmu::TINGKAT_INDUK, true) : RumpunIlmu::RUMPUN;

        return $this->form(new RumpunIlmu(['tingkat' => $tingkat ?: RumpunIlmu::RUMPUN, 'induk_id' => $induk?->id, 'is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        RumpunIlmu::create($this->validasi($request));

        return redirect()->route('sistem.rumpun-ilmu.index')->with('status', 'Rumpun ilmu ditambahkan.');
    }

    public function edit(RumpunIlmu $rumpunIlmu): View
    {
        return $this->form($rumpunIlmu);
    }

    public function update(Request $request, RumpunIlmu $rumpunIlmu): RedirectResponse
    {
        $rumpunIlmu->update($this->validasi($request, $rumpunIlmu));

        return redirect()->route('sistem.rumpun-ilmu.index')->with('status', 'Rumpun ilmu diperbarui.');
    }

    private function form(RumpunIlmu $rumpunIlmu): View
    {
        return view('sistem.rumpun-ilmu.form', [
            'rumpunIlmu' => $rumpunIlmu,
            // Grouped by level so the form can offer only parents one level up.
            'calonInduk' => RumpunIlmu::whereIn('tingkat', [RumpunIlmu::RUMPUN, RumpunIlmu::POHON])
                ->aktifAtau($rumpunIlmu->induk_id)
                ->orderBy('kode')->get()->groupBy('tingkat'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?RumpunIlmu $rumpunIlmu = null): array
    {
        $data = $request->validate([
            'tingkat' => ['required', Rule::in(array_keys(RumpunIlmu::TINGKAT))],
            'kode' => ['required', 'string', 'max:20', Rule::unique('rumpun_ilmu')->ignore($rumpunIlmu)],
            'nama' => ['required', 'string', 'max:255'],
            'induk_id' => ['nullable', 'integer', 'exists:rumpun_ilmu,id'],
        ]);

        $tingkatInduk = RumpunIlmu::TINGKAT_INDUK[$data['tingkat']];
        $induk = $tingkatInduk ? RumpunIlmu::find($data['induk_id'] ?? null) : null;
        if ($tingkatInduk && $induk?->tingkat !== $tingkatInduk) {
            throw ValidationException::withMessages(['induk_id' => 'Pilih induk bertingkat '.strtolower(RumpunIlmu::TINGKAT[$tingkatInduk]).'.']);
        }
        if ($rumpunIlmu && $rumpunIlmu->tingkat !== $data['tingkat'] && $rumpunIlmu->turunan()->exists()) {
            throw ValidationException::withMessages(['tingkat' => 'Tingkat tidak bisa diubah karena entri ini sudah punya turunan.']);
        }

        return ['induk_id' => $induk?->id, 'is_active' => $request->boolean('is_active')] + $data;
    }
}
