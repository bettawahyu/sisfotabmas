<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dosen_id', 'nama_penulis', 'afiliasi', 'urutan', 'is_korespondensi'])]
class PublikasiPenulis extends Model
{
    protected $table = 'publikasi_penulis';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_korespondensi' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Publikasi, $this>
     */
    public function publikasi(): BelongsTo
    {
        return $this->belongsTo(Publikasi::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
