<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dosen_id', 'nama', 'afiliasi', 'urutan'])]
class PatenInventor extends Model
{
    protected $table = 'paten_inventor';

    /**
     * @return BelongsTo<Paten, $this>
     */
    public function paten(): BelongsTo
    {
        return $this->belongsTo(Paten::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
