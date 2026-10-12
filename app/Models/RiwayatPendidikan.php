<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['jenjang', 'institusi', 'program_studi', 'negara', 'tahun_lulus', 'judul_tugas_akhir'])]
class RiwayatPendidikan extends Model
{
    protected $table = 'riwayat_pendidikan';

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
