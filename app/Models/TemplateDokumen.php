<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A template file for proposals and reports; a null skema or tahun applies to all.
 */
#[Fillable(['jenis', 'skema_id', 'tahun', 'nama', 'versi', 'is_active', 'diunggah_oleh'])]
class TemplateDokumen extends Model
{
    use SoftDeletes;

    protected $table = 'template_dokumen';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SkemaHibah, $this>
     */
    public function skema(): BelongsTo
    {
        return $this->belongsTo(SkemaHibah::class, 'skema_id');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }
}
