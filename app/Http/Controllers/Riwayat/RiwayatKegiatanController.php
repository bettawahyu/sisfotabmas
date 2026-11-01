<?php

namespace App\Http\Controllers\Riwayat;

use App\Enums\JenisKegiatan;
use App\Enums\TipeAnggota;
use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\BidangFokus;
use App\Models\Dosen;
use App\Models\KabupatenKota;
use App\Models\Mitra;
use App\Models\RiwayatKegiatan;
use App\Services\AlurKarya;
use App\Services\AlurRiwayat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A lecturer's research or community service records, and the form LPPM and
 * operators use to enter or correct them for any lecturer.
 */
class RiwayatKegiatanController extends Controller
{
    public function index(Request $request, string $jenis, AlurRiwayat $alur): View
    {
        $dosen = $request->user()->dosen;

        return view('riwayat.index', [
            'jenis' => $jenis,
            'daftar' => $dosen === null ? ['berlangsung' => collect(), 'selesai' => collect()] : $alur->milikDosen($dosen, AlurRiwayat::JENIS[$jenis]),
        ]);
    }

    public function create(Request $request, string $jenis): View
    {
        $this->pastikanBolehInput($request);

        return $this->form($request, $jenis, new RiwayatKegiatan([
            'jenis_kegiatan' => AlurRiwayat::JENIS[$jenis],
            'status_kegiatan' => RiwayatKegiatan::SELESAI,
            'sumber_dana' => 'mandiri',
            'tahun_mulai' => now()->year,
        ]));
    }

    public function store(Request $request, string $jenis, AlurRiwayat $alur): RedirectResponse
    {
        $this->pastikanBolehInput($request);
        [$data, $anggota, $mitra, $berkas, $luaran] = $this->validasi($request, $jenis);
        if ($ganda = $this->periksaGanda($request, $alur, $data, null)) {
            return $ganda;
        }
        $alur->simpan(new RiwayatKegiatan, $data, $anggota, $mitra, $berkas, $request->user(), $luaran);

        return $this->kembali($request, $jenis, 'Data tersimpan.');
    }

    public function edit(Request $request, string $jenis, RiwayatKegiatan $riwayat): View
    {
        $this->temukan($request, $jenis, $riwayat);

        return $this->form($request, $jenis, $riwayat->load(['anggota', 'mitra', 'berkas', 'kabupatenKota', 'publikasi', 'hki', 'paten']));
    }

    public function update(Request $request, string $jenis, RiwayatKegiatan $riwayat, AlurRiwayat $alur): RedirectResponse
    {
        $this->temukan($request, $jenis, $riwayat);
        [$data, $anggota, $mitra, $berkas, $luaran] = $this->validasi($request, $jenis);
        if ($ganda = $this->periksaGanda($request, $alur, $data, $riwayat->id)) {
            return $ganda;
        }
        $alur->simpan($riwayat, $data, $anggota, $mitra, $berkas, $request->user(), $luaran);

        return $this->kembali($request, $jenis, 'Perubahan tersimpan.');
    }

    public function destroy(Request $request, string $jenis, RiwayatKegiatan $riwayat, AlurRiwayat $alur): RedirectResponse
    {
        $this->temukan($request, $jenis, $riwayat);
        $alur->hapus($riwayat, $request->user());

        return $this->kembali($request, $jenis, 'Data dihapus.', false);
    }

    /**
     * Checked before the form is sent, so the warning can show in a modal
     * without losing the chosen files.
     */
    public function cekGanda(Request $request, string $jenis, AlurRiwayat $alur): JsonResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:500'],
            'tahun_mulai' => ['required', 'integer', 'between:1950,2100'],
            'kecuali' => ['nullable', 'integer'],
        ]);

        return response()->json(['ganda' => $alur->cariGanda(AlurRiwayat::JENIS[$jenis], $data['judul'], (int) $data['tahun_mulai'], $data['kecuali'] ?? null)]);
    }

    public function berkas(Request $request, Berkas $berkas): StreamedResponse
    {
        $riwayat = $berkas->pemilik;
        abort_unless($riwayat instanceof RiwayatKegiatan && AlurRiwayat::bolehLihat($riwayat, $request->user()), 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }

    private function form(Request $request, string $jenis, RiwayatKegiatan $riwayat): View
    {
        $user = $request->user();
        $lppm = AlurRiwayat::petugasLppm($user);
        $anggota = $riwayat->exists
            ? $riwayat->anggota->map(fn ($a) => ['tipe' => $a->tipe->value, 'dosen_id' => $a->dosen_id, 'nama' => $a->dosen_id ? null : $a->nama, 'keterangan' => $a->keterangan, 'peran' => $a->peran])->all()
            : [['tipe' => TipeAnggota::Dosen->value, 'dosen_id' => $lppm ? null : $user->dosen?->id, 'nama' => null, 'keterangan' => null, 'peran' => RiwayatKegiatan::KETUA]];

        $terpilih = $riwayat->exists ? $riwayat->mitra->pluck('id')->all() : [];
        // Outputs to link come from the team's lecturers; a new record by LPPM offers them once saved.
        $dosenTim = $riwayat->exists ? $riwayat->anggota->pluck('dosen_id')->filter()->values()->all() : array_filter([$user->dosen?->id]);
        $luaranTerpilih = old('luaran', $riwayat->exists ? $riwayat->karyaTertaut()->map(fn ($k) => AlurKarya::slug($k).':'.$k->id)->all() : []);

        return view('riwayat.form', [
            'jenis' => $jenis,
            'riwayat' => $riwayat,
            'lppm' => $lppm,
            'anggota' => array_pad($anggota, max(5, count($anggota) + 2), []),
            'dosen' => Dosen::orderBy('nama')->get(['id', 'nama', 'gelar_depan', 'gelar_belakang']),
            'bidangFokus' => BidangFokus::where('jenis_kegiatan', AlurRiwayat::JENIS[$jenis])->aktifAtau($riwayat->bidang_fokus_id)->orderBy('nama')->get(),
            'wilayah' => KabupatenKota::pilihan(),
            'mitra' => Mitra::bisaDipilih($user, $terpilih)->orderBy('nama')->get(['id', 'nama', 'bidang', 'status']),
            'mitraTerpilih' => array_map('intval', old('mitra', $terpilih)),
            'pilihanKarya' => AlurRiwayat::pilihanKarya(array_values($dosenTim), $riwayat),
            'luaranTerpilih' => $luaranTerpilih,
        ]);
    }

    /**
     * @return array{array<string, mixed>, list<array<string, mixed>>, list<int>, array<string, mixed>, list<string>}
     */
    private function validasi(Request $request, string $jenis): array
    {
        $pkm = AlurRiwayat::JENIS[$jenis] === JenisKegiatan::Pkm;
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:500'],
            'tahun_mulai' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
            'tahun_selesai' => ['nullable', 'integer', 'gte:tahun_mulai', 'max:'.(now()->year + 6)],
            'status_kegiatan' => ['required', Rule::in(array_keys(RiwayatKegiatan::STATUS_KEGIATAN))],
            'sumber_dana' => ['required', Rule::in(array_keys(RiwayatKegiatan::SUMBER_DANA))],
            'pemberi_dana' => ['nullable', 'string', 'max:255'],
            'skema' => ['nullable', 'string', 'max:255'],
            'nilai_dana' => ['nullable', 'integer', 'min:0'],
            'no_kontrak' => ['nullable', 'string', 'max:255'],
            'bidang_fokus_id' => ['nullable', Rule::exists('bidang_fokus', 'id')->where('jenis_kegiatan', AlurRiwayat::JENIS[$jenis]->value)],
            'kabupaten_kota_kode' => ['nullable', 'exists:kabupaten_kota,kode'],
            'alamat_lokasi' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:5000'],
            'jumlah_sasaran' => ['nullable', 'integer', 'min:0'],
            'catatan_dampak' => ['nullable', 'string', 'max:5000'],
            'tautan_video' => ['nullable', 'url', 'max:255'],
            'tautan_media' => ['nullable', 'string', 'max:3000'],

            'anggota' => ['required', 'array', 'max:30'],
            'anggota.*.tipe' => ['nullable', Rule::enum(TipeAnggota::class)],
            'anggota.*.dosen_id' => ['nullable', 'integer', 'exists:dosen,id'],
            'anggota.*.nama' => ['nullable', 'string', 'max:255'],
            'anggota.*.keterangan' => ['nullable', 'string', 'max:255'],
            'ketua' => ['required', 'integer'],
            'mitra' => ['nullable', 'array', 'max:20'],
            'mitra.*' => ['integer'],
            'luaran' => ['nullable', 'array', 'max:50'],
            'luaran.*' => ['string', 'regex:/^(publikasi|hki|paten):\d+$/'],

            'bukti' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'laporan' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'surat_mitra' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'foto' => ['nullable', 'array', 'max:10'],
            'foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['integer'],
        ], [], ['tahun_selesai' => 'tahun selesai', 'tahun_mulai' => 'tahun mulai']);

        $media = array_filter(array_map('trim', preg_split('/\R/', (string) ($data['tautan_media'] ?? ''))));
        foreach ($media as $tautan) {
            if (filter_var($tautan, FILTER_VALIDATE_URL) === false) {
                throw ValidationException::withMessages(['tautan_media' => "Tautan media \"{$tautan}\" tidak valid."]);
            }
        }

        $anggota = collect($data['anggota'])->values()
            ->map(fn ($a, $i) => $a + ['peran' => (int) $data['ketua'] === $i ? RiwayatKegiatan::KETUA : RiwayatKegiatan::ANGGOTA])
            ->all();

        $isian = Arr::only($data, ['judul', 'tahun_mulai', 'tahun_selesai', 'status_kegiatan', 'sumber_dana', 'pemberi_dana', 'skema',
            'nilai_dana', 'no_kontrak', 'bidang_fokus_id', 'kabupaten_kota_kode', 'alamat_lokasi', 'ringkasan']);
        $isian += $pkm
            ? ['jumlah_sasaran' => $data['jumlah_sasaran'] ?? null, 'catatan_dampak' => $data['catatan_dampak'] ?? null,
                'tautan_video' => $data['tautan_video'] ?? null, 'tautan_media' => $media ? implode("\n", $media) : null]
            : ['jumlah_sasaran' => null, 'catatan_dampak' => null, 'tautan_video' => null, 'tautan_media' => null];
        $isian['jenis_kegiatan'] = AlurRiwayat::JENIS[$jenis];

        $berkas = [
            'bukti' => $request->file('bukti'),
            'laporan' => $request->file('laporan'),
            'surat_mitra' => $pkm ? $request->file('surat_mitra') : null,
            'foto' => $pkm ? ($request->file('foto') ?? []) : [],
            'hapus_foto' => array_map('intval', $data['hapus_foto'] ?? []),
        ];

        return [$isian, $anggota, array_map('intval', $data['mitra'] ?? []), $berkas, array_values($data['luaran'] ?? [])];
    }

    /**
     * The modal check runs in the browser; this catches a form sent without it.
     *
     * @param  array<string, mixed>  $data
     */
    private function periksaGanda(Request $request, AlurRiwayat $alur, array $data, ?int $kecuali): ?RedirectResponse
    {
        if ($request->boolean('abaikan_ganda')) {
            return null;
        }
        $ganda = $alur->cariGanda($data['jenis_kegiatan'], $data['judul'], (int) $data['tahun_mulai'], $kecuali);

        return $ganda->isEmpty() ? null : back()->withInput()->with('ganda', $ganda->all());
    }

    private function pastikanBolehInput(Request $request): void
    {
        abort_unless(AlurRiwayat::petugasLppm($request->user()) || $request->user()->dosen !== null, 403);
    }

    private function temukan(Request $request, string $jenis, RiwayatKegiatan $riwayat): void
    {
        abort_unless($riwayat->jenis_kegiatan === AlurRiwayat::JENIS[$jenis], 404);
        abort_unless(AlurRiwayat::bolehUbah($riwayat->loadMissing('anggota'), $request->user()), 403);
    }

    private function kembali(Request $request, string $jenis, string $pesan, bool $simpan = true): RedirectResponse
    {
        if (AlurRiwayat::petugasLppm($request->user())) {
            return redirect()->route('riwayat-dosen.index', $jenis)->with('status', $pesan.($simpan ? ' Data yang diisi LPPM langsung tervalidasi.' : ''));
        }

        return redirect()->route('riwayat.index', $jenis)->with('status', $pesan.($simpan ? ' Data menunggu verifikasi LPPM.' : ''));
    }
}
