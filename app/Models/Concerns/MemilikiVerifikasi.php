<?php

namespace App\Models\Concerns;

use App\Models\Berkas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by publikasi, HKI and paten: the kaprodi checks an entry first, then
 * LPPM marks it valid. Only valid entries count in reports.
 */
trait MemilikiVerifikasi
{
    public const MENUNGGU = 'menunggu';

    public const DISETUJUI_KAPRODI = 'disetujui_kaprodi';

    public const VALID = 'valid';

    public const DITOLAK = 'ditolak';

    public function initializeMemilikiVerifikasi(): void
    {
        $this->mergeCasts([
            'diverifikasi_kaprodi_at' => 'datetime',
            'diverifikasi_lppm_at' => 'datetime',
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diinput_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikatorKaprodi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_kaprodi_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikatorLppm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_lppm_oleh');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->where('status_verifikasi', self::VALID);
    }
}
