<?php

namespace App\Http\Controllers\Karya;

use App\Models\Paten;
use Illuminate\Validation\Rule;

class PatenController extends KaryaController
{
    protected string $jenis = 'paten';

    protected function aturan(?int $id): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(Paten::JENIS))],
            'judul' => ['required', 'string', 'max:500'],
            'abstrak' => ['nullable', 'string', 'max:5000'],
            'pemegang_paten' => ['nullable', 'string', 'max:255'],
            'konsultan_ki' => ['nullable', 'string', 'max:255'],
            'no_permohonan' => ['nullable', 'string', 'max:50'],
            'tgl_penerimaan' => ['nullable', 'date'],
            'tgl_publikasi' => ['nullable', 'date'],
            'klasifikasi_ipc' => ['nullable', 'string', 'max:60'],
            'jumlah_klaim' => ['nullable', 'integer', 'between:1,1000'],
            'no_paten' => ['nullable', 'string', 'max:50'],
            'tgl_pemberian' => ['nullable', 'date'],
            'masa_berlaku_sampai' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Paten::STATUS))],
            'url_pdki' => ['nullable', 'url', 'max:255'],
        ];
    }
}
