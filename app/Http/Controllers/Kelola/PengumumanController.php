<?php

namespace App\Http\Controllers\Kelola;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\Pengumuman;
use App\Models\PeriodeHibah;
use App\Models\Role;
use App\Services\SiaranPengumuman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Announcements, kept by LPPM admins and operators.
 */
class PengumumanController extends Controller
{
    public function index(): View
    {
        return view('kelola.pengumuman.index', [
            'pengumuman' => Pengumuman::with('periode')->orderByDesc('is_pinned')->orderByDesc('terbit_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('kelola.pengumuman.form', $this->dataForm(new Pengumuman([
            'kategori' => Pengumuman::UMUM,
            'target_peran' => Pengumuman::SEMUA,
            'terbit_at' => now(),
        ])));
    }

    public function store(Request $request, SiaranPengumuman $siaran): RedirectResponse
    {
        $data = $this->validasi($request);

        $pengumuman = DB::transaction(function () use ($request, $data) {
            $pengumuman = Pengumuman::create([
                ...$data,
                'slug' => Pengumuman::slugBaru($data['judul']),
                'dibuat_oleh' => $request->user()->id,
            ]);
            $this->simpanBerkas($request, $pengumuman);

            return $pengumuman;
        });

        $siaran->kirim($pengumuman);

        return redirect()->route('kelola.pengumuman.index')->with('status', $pengumuman->sedangTampil()
            ? 'Pengumuman diterbitkan dan dikirim ke pembacanya.'
            : 'Pengumuman disimpan dan akan terbit sesuai jadwal.');
    }

    public function edit(Pengumuman $pengumuman): View
    {
        return view('kelola.pengumuman.form', $this->dataForm($pengumuman->load(['lampiran', 'gambar'])));
    }

    public function update(Request $request, Pengumuman $pengumuman, SiaranPengumuman $siaran): RedirectResponse
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($request, $pengumuman, $data) {
            $pengumuman->update($data);
            if ($request->boolean('hapus_lampiran')) {
                $pengumuman->lampiran?->delete();
            }
            if ($request->boolean('hapus_gambar')) {
                $pengumuman->gambar?->delete();
            }
            $this->simpanBerkas($request, $pengumuman);
        });

        // A draft moved to an earlier date may now be live; send it if it was never sent.
        $siaran->kirim($pengumuman->refresh());

        return redirect()->route('kelola.pengumuman.index')->with('status', 'Pengumuman diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->delete();

        return redirect()->route('kelola.pengumuman.index')->with('status', 'Pengumuman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Pengumuman $pengumuman): array
    {
        return [
            'pengumuman' => $pengumuman,
            'periode' => PeriodeHibah::with('skema')->orderByDesc('tahun_anggaran')->orderBy('nama')->get(),
            'peran' => [Pengumuman::SEMUA => 'Semua pengguna'] + Role::ALL,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string', 'max:20000'],
            'kategori' => ['required', Rule::in(array_keys(Pengumuman::KATEGORI))],
            'periode_id' => ['nullable', Rule::exists('periode_hibah', 'id')],
            'target_peran' => ['required', Rule::in([Pengumuman::SEMUA, ...array_keys(Role::ALL)])],
            'is_pinned' => ['boolean'],
            'terbit_at' => ['required', 'date'],
            'berakhir_at' => ['nullable', 'date', 'after:terbit_at'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'hapus_lampiran' => ['boolean'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'hapus_gambar' => ['boolean'],
        ]);
        $data['is_pinned'] = $request->boolean('is_pinned');
        unset($data['lampiran'], $data['hapus_lampiran'], $data['gambar'], $data['hapus_gambar']);

        return $data;
    }

    /**
     * Store the uploaded PDF and featured image; a new one supersedes the old.
     */
    private function simpanBerkas(Request $request, Pengumuman $pengumuman): void
    {
        foreach (['lampiran' => Berkas::LAMPIRAN, 'gambar' => Berkas::GAMBAR_UNGGULAN] as $field => $kategori) {
            if ($request->hasFile($field)) {
                $this->simpanFile($request, $pengumuman, $field, $kategori);
            }
        }
    }

    private function simpanFile(Request $request, Pengumuman $pengumuman, string $field, string $kategori): void
    {
        $file = $request->file($field);
        $pengumuman->berkas()->create([
            'kategori' => $kategori,
            'nama_asli' => $file->getClientOriginalName(),
            'path' => $file->store("pengumuman/{$pengumuman->id}"),
            'mime' => $file->getMimeType(),
            'ukuran_byte' => $file->getSize(),
            'diunggah_oleh' => $request->user()->id,
        ]);
    }
}
