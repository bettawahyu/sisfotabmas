<?php

namespace App\Http\Controllers\Karya;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Hki;
use App\Models\Paten;
use App\Models\Publikasi;
use App\Models\PublikasiPenulis;
use App\Services\AlurKarya;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * A lecturer's own publications, HKI or patents. Each subclass names its
 * model and fields; the list and form pages are shared.
 */
abstract class KaryaController extends Controller
{
    /** The slug in AlurKarya::JENIS. */
    protected string $jenis;

    /**
     * Validation rules for the entry's own fields.
     *
     * @return array<string, mixed>
     */
    abstract protected function aturan(?int $id): array;

    public function index(Request $request): View
    {
        $dosen = $request->user()->dosen;
        $model = AlurKarya::JENIS[$this->jenis];

        return view('karya.index', [
            'jenis' => $this->jenis,
            'karya' => $dosen === null ? collect() : $model::milikDosen($dosen)
                ->with(['kontributor', 'luaran.proposal'])
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $model = AlurKarya::JENIS[$this->jenis];

        return $this->form(new $model, $request);
    }

    public function store(Request $request, AlurKarya $alur): RedirectResponse
    {
        $model = AlurKarya::JENIS[$this->jenis];
        [$isian, $kontributor] = $this->validasi($request, null);
        $alur->simpan(new $model, $isian, $kontributor, $request->user());

        return redirect()->route("karya.{$this->jenis}.index")->with('status', 'Data tersimpan dan menunggu verifikasi LPPM.');
    }

    public function edit(Request $request, int $karya): View
    {
        return $this->form($this->temukan($request, $karya), $request);
    }

    public function update(Request $request, int $karya, AlurKarya $alur): RedirectResponse
    {
        $entri = $this->temukan($request, $karya);
        [$isian, $kontributor] = $this->validasi($request, $entri->id);
        $alur->simpan($entri, $isian, $kontributor, $request->user());

        return redirect()->route("karya.{$this->jenis}.index")->with('status', 'Perubahan tersimpan dan menunggu verifikasi LPPM.');
    }

    public function destroy(Request $request, int $karya, AlurKarya $alur): RedirectResponse
    {
        $alur->hapus($this->temukan($request, $karya));

        return redirect()->route("karya.{$this->jenis}.index")->with('status', 'Data dihapus.');
    }

    private function form(Publikasi|Hki|Paten $karya, Request $request): View
    {
        $kontributor = $karya->exists ? $karya->kontributor->map(fn ($k) => [
            'dosen_id' => $k->dosen_id,
            'nama' => $k instanceof PublikasiPenulis ? $k->nama_penulis : $k->nama,
            'afiliasi' => $k->afiliasi,
            'is_korespondensi' => $k instanceof PublikasiPenulis && $k->is_korespondensi,
        ])->all() : [['dosen_id' => $request->user()->dosen?->id, 'nama' => null, 'afiliasi' => null, 'is_korespondensi' => true]];

        return view('karya.form', [
            'jenis' => $this->jenis,
            'karya' => $karya,
            'kontributor' => array_pad($kontributor, max(5, count($kontributor) + 2), []),
            'dosen' => Dosen::orderBy('nama')->get(['id', 'nama', 'gelar_depan', 'gelar_belakang']),
        ]);
    }

    private function temukan(Request $request, int $id): Publikasi|Hki|Paten
    {
        $model = AlurKarya::JENIS[$this->jenis];
        $karya = $model::with('kontributor')->findOrFail($id);
        abort_unless($karya->dimilikiOleh($request->user()), 403);

        return $karya;
    }

    /**
     * The entry's own fields, and the contributor rows as typed in order;
     * AlurKarya drops the empty rows.
     *
     * @return array{array<string, mixed>, list<array<string, mixed>>}
     */
    private function validasi(Request $request, ?int $id): array
    {
        $data = $request->validate($this->aturan($id) + [
            'kontributor' => ['required', 'array', 'max:30'],
            'kontributor.*.dosen_id' => ['nullable', 'integer', 'exists:dosen,id'],
            'kontributor.*.nama' => ['nullable', 'string', 'max:255'],
            'kontributor.*.afiliasi' => ['nullable', 'string', 'max:255'],
            'korespondensi' => ['nullable', 'integer'],
        ]);

        $kontributor = collect($data['kontributor'])->values()
            ->map(fn ($k, $i) => $k + ['is_korespondensi' => isset($data['korespondensi']) && (int) $data['korespondensi'] === $i])
            ->all();

        return [Arr::except($data, ['kontributor', 'korespondensi']), $kontributor];
    }
}
