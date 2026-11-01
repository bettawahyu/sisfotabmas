<?php

namespace App\Models;

use App\Enums\TipeAnggota;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipe', 'dosen_id', 'nama', 'keterangan', 'peran', 'urutan'])]
class RiwayatKegiatanAnggota extends Model
{
    protected $table = 'riwayat_kegiatan_anggota';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => TipeAnggota::class,
        ];
    }

    /**
     * @return BelongsTo<RiwayatKegiatan, $this>
     */
    public function riwayatKegiatan(): BelongsTo
    {
        return $this->belongsTo(RiwayatKegiatan::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
