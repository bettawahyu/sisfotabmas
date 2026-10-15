<?php

namespace App\Http\Controllers;

use App\Models\PeriodeHibah;
use App\Models\ProgramStudi;
use App\Models\Publikasi;
use App\Models\SkemaHibah;
use App\Services\UnduhData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Style\Style;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel downloads of all proposals and of the publications, HKI and patents,
 * for LPPM, operators, finance and the institution's and units' heads.
 */
class UnduhDataController extends Controller
{
    public function index(): View
    {
        $tahun = PeriodeHibah::distinct()->pluck('tahun_anggaran')
            ->merge(Publikasi::distinct()->pluck('tahun'))
            ->push((int) now()->year)
            ->unique()->sortDesc()->values();

        return view('unduh-data.index', [
            'tahun' => $tahun,
            'skema' => SkemaHibah::orderBy('nama')->get(),
            'prodi' => ProgramStudi::with('fakultas')->orderBy('nama')->get(),
            'jenisKarya' => UnduhData::pilihanJenisKarya(),
        ]);
    }

    public function proposal(Request $request, UnduhData $unduh): StreamedResponse
    {
        $filter = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'skema_id' => ['nullable', Rule::exists('skema_hibah', 'id')],
            'prodi_id' => ['nullable', Rule::exists('program_studi', 'id')],
        ]);

        return $this->excel(new FastExcel($unduh->proposal($filter)), $this->namaFile('proposal', $filter['tahun'] ?? null));
    }

    public function karya(Request $request, UnduhData $unduh): StreamedResponse
    {
        $pilihan = collect(UnduhData::pilihanJenisKarya())
            ->flatMap(fn ($jenis, $kelompok) => [$kelompok, ...array_map(fn ($j) => "{$kelompok}:{$j}", array_keys($jenis))]);
        $filter = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'jenis' => ['nullable', Rule::in($pilihan->all())],
            'prodi_id' => ['nullable', Rule::exists('program_studi', 'id')],
        ]) + ['hanya_valid' => $request->boolean('hanya_valid')];

        return $this->excel(new FastExcel(new SheetCollection($unduh->karya($filter))), $this->namaFile('publikasi-ki', $filter['tahun'] ?? null));
    }

    private function namaFile(string $dasar, int|string|null $tahun): string
    {
        return $dasar.($tahun ? "-{$tahun}" : '').'-'.Date::now()->format('Ymd-His').'.xlsx';
    }

    private function excel(FastExcel $excel, string $nama): StreamedResponse
    {
        return $excel->headerStyle((new Style)->setFontBold())->download($nama);
    }
}
