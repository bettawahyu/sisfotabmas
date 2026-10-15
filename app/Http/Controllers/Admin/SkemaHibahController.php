<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JabatanFungsional;
use App\Enums\JenisKegiatan;
use App\Enums\SumberDana;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\JenisLuaran;
use App\Models\SkemaHibah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SkemaHibahController extends Controller
{
    public function index(): View
    {
        return view('admin.skema.index', ['skema' => SkemaHibah::orderBy('jenis_kegiatan')->orderBy('nama')->get()]);
    }

    public function create(): View
    {
        return $this->form(new SkemaHibah(['lama_maks_tahun' => 1, 'is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $luaran = $this->validasiLuaranWajib($request);
        $skema = DB::transaction(function () use ($data, $luaran) {
            $skema = SkemaHibah::create($data);
            $skema->jenisLuaranWajib()->sync($luaran);

            return $skema;
        });

        return redirect()->route('admin.skema.index')->with('status', 'Skema hibah dibuat.');
    }

    public function edit(SkemaHibah $skema): View
    {
        return $this->form($skema->load('jenisLuaranWajib'));
    }

    public function update(Request $request, SkemaHibah $skema): RedirectResponse
    {
        $data = $this->validasi($request, $skema);
        $luaran = $this->validasiLuaranWajib($request);
        DB::transaction(function () use ($skema, $data, $luaran) {
            $skema->update($data);
            $skema->jenisLuaranWajib()->sync($luaran);
        });

        return redirect()->route('admin.skema.index')->with('status', 'Skema hibah diperbarui.');
    }

    private function form(SkemaHibah $skema): View
    {
        $terpilih = $skema->exists ? $skema->jenisLuaranWajib->pluck('pivot.jumlah_min', 'id') : collect();

        return view('admin.skema.form', [
            'skema' => $skema,
            // Inactive types stay listed while the scheme still requires them.
            'jenisLuaran' => JenisLuaran::berurutan()->get()
                ->filter(fn (JenisLuaran $j) => $j->is_active || $terpilih->has($j->id))->values(),
            'luaranTerpilih' => $terpilih,
        ]);
    }

    /**
     * Ticked output types, each with how many a proposal must list, ready for sync().
     *
     * @return array<int, array{jumlah_min: int}>
     */
    private function validasiLuaranWajib(Request $request): array
    {
        $data = $request->validate([
            'luaran_wajib_jenis' => ['array'],
            'luaran_wajib_jenis.*' => ['integer', 'exists:jenis_luaran,id'],
            'luaran_wajib_jumlah' => ['array'],
            'luaran_wajib_jumlah.*' => ['nullable', 'integer', 'between:1,20'],
        ]);

        return collect($data['luaran_wajib_jenis'] ?? [])->mapWithKeys(fn ($id) => [
            (int) $id => ['jumlah_min' => (int) ($data['luaran_wajib_jumlah'][$id] ?? 1) ?: 1],
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?SkemaHibah $skema = null): array
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', Rule::unique('skema_hibah')->ignore($skema)],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kegiatan' => ['required', Rule::enum(JenisKegiatan::class)],
            'sumber_dana' => ['required', Rule::enum(SumberDana::class)],
            'is_multitahun' => ['boolean'],
            'lama_maks_tahun' => ['required', 'integer', 'between:1,5'],
            'dana_maksimal_default' => ['required', 'integer', 'min:0'],
            'tkt_min' => ['nullable', 'integer', 'between:1,9'],
            'honor_diizinkan' => ['boolean'],
            'batas_honor_persen' => ['nullable', 'integer', 'between:1,100'],
            'luaran_wajib' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'jabatan_min' => ['nullable', Rule::enum(JabatanFungsional::class)],
            'pendidikan_min' => ['nullable', Rule::in(Dosen::PENDIDIKAN)],
            'anggota_dosen_min' => ['nullable', 'integer', 'between:0,20'],
            'anggota_dosen_maks' => ['nullable', 'integer', 'between:0,20'],
            'anggota_mahasiswa_min' => ['nullable', 'integer', 'between:0,20'],
        ]);

        if (isset($data['anggota_dosen_min'], $data['anggota_dosen_maks']) && $data['anggota_dosen_maks'] < $data['anggota_dosen_min']) {
            throw ValidationException::withMessages(['anggota_dosen_maks' => 'Maksimal anggota dosen tidak boleh kurang dari minimalnya.']);
        }

        foreach (['is_multitahun', 'honor_diizinkan', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        if (! $data['is_multitahun']) {
            $data['lama_maks_tahun'] = 1;
        }

        return $data;
    }
}
