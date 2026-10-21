<?php

namespace App\Http\Controllers;

use App\Enums\JabatanFungsional;
use App\Models\Dosen;
use App\Models\Role;
use App\Services\DataDosen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Style\Style;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lecturers' full qualifications, read-only, for operators, LPPM and unit
 * heads, with Excel downloads of what the filter shows or of everyone.
 */
class DataDosenController extends Controller
{
    public function index(Request $request, DataDosen $data): View
    {
        $filter = $this->filter($request);

        return view('data-dosen.index', [
            'dosen' => $data->query($request->user(), $filter)->withCount('publikasi')->withCount('hki')->get(),
            'filter' => $filter,
            'prodi' => $data->prodi($request->user()),
            'lihatSemua' => DataDosen::bolehLihatSemua($request->user()),
            'kelolaAkun' => $request->user()->hasRole(Role::ADMIN_LPPM),
        ]);
    }

    public function unduh(Request $request, DataDosen $data): StreamedResponse
    {
        $dosen = $data->query($request->user(), $this->filter($request))->get();

        return $this->excel(new FastExcel(new SheetCollection($data->sheetTersaring($dosen))), 'data-dosen');
    }

    public function unduhSemua(Request $request, DataDosen $data): StreamedResponse
    {
        $dosen = $data->query($request->user())->get();

        return $this->excel(new FastExcel($data->sheetLengkap($dosen)), 'data-dosen-lengkap');
    }

    /**
     * LPPM switches a lecturer's account off or back on; the records stay.
     */
    public function aktif(Request $request, Dosen $dosen): RedirectResponse
    {
        abort_if($dosen->user === null, 404);
        if (! $request->boolean('aktif') && $dosen->user->is($request->user())) {
            return back()->with('gagal', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }
        $dosen->user->aturAktif($request->boolean('aktif'));

        return back()->with('status', "Akun {$dosen->namaLengkap()} ".($dosen->user->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function filter(Request $request): array
    {
        return $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer', Rule::exists('program_studi', 'id')],
            'jabatan_fungsional' => ['nullable', Rule::enum(JabatanFungsional::class)],
            'pendidikan_terakhir' => ['nullable', Rule::in(Dosen::PENDIDIKAN)],
        ]);
    }

    private function excel(FastExcel $excel, string $dasar): StreamedResponse
    {
        // Names and titles are typed by lecturers: a leading "=" must stay text, not become a live formula.
        return $excel->escapeFormulas()->headerStyle((new Style)->setFontBold())
            ->download($dasar.'-'.Date::now()->format('Ymd-His').'.xlsx');
    }
}
