<?php

namespace App\Models;

use Database\Factories\PengumumanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['judul', 'slug', 'isi', 'kategori', 'periode_id', 'target_peran', 'is_pinned', 'terbit_at', 'berakhir_at', 'dibuat_oleh'])]
class Pengumuman extends Model
{
    /** @use HasFactory<PengumumanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'pengumuman';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'terbit_at' => 'datetime',
            'berakhir_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PeriodeHibah, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeHibah::class, 'periode_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * Published and not yet expired, pinned ones first.
     *
     * @param  Builder<Pengumuman>  $query
     */
    public function scopeTampil(Builder $query): void
    {
        $query->where('terbit_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('berakhir_at')->orWhere('berakhir_at', '>', now()))
            ->orderByDesc('is_pinned')
            ->orderByDesc('terbit_at');
    }
}
