<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kriteria_id', 'skor', 'catatan'])]
class NilaiPenilaian extends Model
{
    protected $table = 'nilai_penilaian';

    /**
     * @return BelongsTo<Penilaian, $this>
     */
    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    /**
     * @return BelongsTo<KriteriaPenilaian, $this>
     */
    public function kriteria(): BelongsTo
    {
        return $this->belongsTo(KriteriaPenilaian::class, 'kriteria_id');
    }
}
