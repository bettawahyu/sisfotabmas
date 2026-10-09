<?php

namespace App\Models;

use App\Enums\JenisKegiatan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['jenis_kegiatan', 'kode', 'nama', 'induk_id', 'is_active'])]
class BidangFokus extends Model
{
    protected $table = 'bidang_fokus';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kegiatan' => JenisKegiatan::class,
            'is_active' => 'boolean',
        ];
    }
}
