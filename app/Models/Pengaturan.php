<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * System content the system admin can change without a deploy, by key.
 */
#[Fillable(['kunci', 'nilai', 'tipe', 'keterangan', 'diubah_oleh'])]
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    public static function nilai(string $kunci, ?string $bawaan = null): ?string
    {
        return static::where('kunci', $kunci)->value('nilai') ?? $bawaan;
    }

    public static function simpan(string $kunci, ?string $nilai, ?User $oleh = null): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai, 'diubah_oleh' => $oleh?->id]);
    }
}
