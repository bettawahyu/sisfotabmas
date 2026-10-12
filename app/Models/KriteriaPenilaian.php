<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rubric line of a call, for one review stage.
 */
#[Fillable(['tahap', 'nama', 'bobot', 'skala_maks', 'urutan'])]
class KriteriaPenilaian extends Model
{
    protected $table = 'kriteria_penilaian';

    /**
     * @return BelongsTo<PeriodeHibah, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeHibah::class, 'periode_id');
    }

    /**
     * @return HasMany<NilaiPenilaian, $this>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(NilaiPenilaian::class, 'kriteria_id');
    }
}
