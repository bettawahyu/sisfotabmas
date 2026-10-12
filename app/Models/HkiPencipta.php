<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dosen_id', 'nama', 'afiliasi', 'urutan'])]
class HkiPencipta extends Model
{
    protected $table = 'hki_pencipta';

    /**
     * @return BelongsTo<Hki, $this>
     */
    public function hki(): BelongsTo
    {
        return $this->belongsTo(Hki::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
