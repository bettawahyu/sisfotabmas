<?php

namespace App\Http\Controllers;

use App\Enums\JabatanFungsional;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Services\DataDosen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
            'dosen' => $data->query($request->user(), $filter)->withCount('publikasi')->get(),
            'filter' => $filter,
            'prodi' => $this->prodi($request->user()),
            'lihatSemua' => DataDosen::bolehLihatSemua($request->user()),
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

    /**
     * The programmes a viewer may filter by: a unit head sees only their own
     * programme, or the programmes of their faculty.
     *
     * @return Collection<int, ProgramStudi>
     */
    private function prodi(User $user): Collection
    {
        $peran = DataDosen::bolehLihatSemua($user) ? null : $user->roles->firstWhere('kode', Role::PIMPINAN_UNIT);

        return ProgramStudi::with('fakultas')
            ->when($peran?->pivot->prodi_id, fn (Builder $q, $prodi) => $q->whereKey($prodi))
            ->when($peran?->pivot->fakultas_id, fn (Builder $q, $fakultas) => $q->where('fakultas_id', $fakultas))
            ->orderBy('nama')->get();
    }

    private function excel(FastExcel $excel, string $dasar): StreamedResponse
    {
        // Names and titles are typed by lecturers: a leading "=" must stay text, not become a live formula.
        return $excel->escapeFormulas()->headerStyle((new Style)->setFontBold())
            ->download($dasar.'-'.Date::now()->format('Ymd-His').'.xlsx');
    }
}
