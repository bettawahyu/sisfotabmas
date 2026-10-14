<?php

namespace App\Http\Controllers\Karya;

use App\Models\Hki;
use Illuminate\Validation\Rule;

class HkiController extends KaryaController
{
    protected string $jenis = 'hki';

    protected function aturan(?int $id): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(Hki::JENIS))],
            'jenis_ciptaan' => ['nullable', 'string', 'max:60'],
            'judul' => ['required', 'string', 'max:500'],
            'uraian' => ['nullable', 'string', 'max:5000'],
            'pemegang_hak' => ['nullable', 'string', 'max:255'],
            'no_permohonan' => ['nullable', 'string', 'max:50'],
            'tgl_permohonan' => ['nullable', 'date'],
            'no_pencatatan' => ['nullable', 'string', 'max:50'],
            'tgl_pencatatan' => ['nullable', 'date'],
            'masa_berlaku_sampai' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Hki::STATUS))],
            'url_pdki' => ['nullable', 'url', 'max:255'],
        ];
    }
}
