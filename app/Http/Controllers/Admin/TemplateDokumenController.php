<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\SkemaHibah;
use App\Models\TemplateDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Template files lecturers download for proposals and reports.
 */
class TemplateDokumenController extends Controller
{
    public function index(): View
    {
        return view('admin.template.index', [
            'template' => TemplateDokumen::with(['skema', 'berkasTerbaru'])->orderBy('jenis')->orderByDesc('tahun')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.template.form', [
            'template' => new TemplateDokumen(['jenis' => TemplateDokumen::SUBSTANSI, 'tahun' => now()->year, 'is_active' => true]),
            'skema' => SkemaHibah::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, baru: true);

        DB::transaction(function () use ($request, $data) {
            $template = TemplateDokumen::create([...$data, 'diunggah_oleh' => $request->user()->id]);
            $this->simpanBerkas($request, $template);
        });

        return redirect()->route('admin.template.index')->with('status', 'Templat diunggah.');
    }

    public function edit(TemplateDokumen $template): View
    {
        return view('admin.template.form', [
            'template' => $template->load('berkasTerbaru'),
            'skema' => SkemaHibah::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, TemplateDokumen $template): RedirectResponse
    {
        $data = $this->validasi($request, baru: false);

        DB::transaction(function () use ($request, $template, $data) {
            $template->update($data);
            $this->simpanBerkas($request, $template);
        });

        return redirect()->route('admin.template.index')->with('status', 'Templat diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, bool $baru): array
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(TemplateDokumen::JENIS))],
            'nama' => ['required', 'string', 'max:255'],
            'skema_id' => ['nullable', Rule::exists('skema_hibah', 'id')],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'versi' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'berkas' => [$baru ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['berkas']);

        return $data;
    }

    private function simpanBerkas(Request $request, TemplateDokumen $template): void
    {
        if (! $request->hasFile('berkas')) {
            return;
        }

        $file = $request->file('berkas');
        $template->berkas()->create([
            'kategori' => Berkas::TEMPLAT,
            'nama_asli' => $file->getClientOriginalName(),
            'path' => $file->store("template/{$template->id}"),
            'mime' => $file->getMimeType(),
            'ukuran_byte' => $file->getSize(),
            'diunggah_oleh' => $request->user()->id,
        ]);
    }
}
