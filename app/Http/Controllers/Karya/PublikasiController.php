<?php

namespace App\Http\Controllers\Karya;

use App\Models\Publikasi;
use Illuminate\Validation\Rule;

class PublikasiController extends KaryaController
{
    protected string $jenis = 'publikasi';

    protected function aturan(?int $id): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(Publikasi::JENIS))],
            'judul' => ['required', 'string', 'max:500'],
            'abstrak' => ['nullable', 'string', 'max:5000'],
            'nama_wadah' => ['nullable', 'string', 'max:255'],
            'issn_isbn' => ['nullable', 'string', 'max:30'],
            'volume' => ['nullable', 'string', 'max:20'],
            'nomor' => ['nullable', 'string', 'max:20'],
            'halaman' => ['nullable', 'string', 'max:30'],
            'tahun' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
            'tgl_terbit' => ['nullable', 'date'],
            'doi' => ['nullable', 'string', 'max:255', Rule::unique('publikasi', 'doi')->ignore($id)],
            'url' => ['nullable', 'url', 'max:255'],
            'peringkat' => ['nullable', Rule::exists('peringkat_jurnal', 'kode')],
            'status' => ['required', Rule::in(array_keys(Publikasi::STATUS))],
        ];
    }
}
