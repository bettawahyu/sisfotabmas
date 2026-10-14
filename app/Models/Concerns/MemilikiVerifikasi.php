<?php

namespace App\Models\Concerns;

use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\HkiPencipta;
use App\Models\Luaran;
use App\Models\PatenInventor;
use App\Models\PublikasiPenulis;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public const STATUS_VERIFIKASI = [
        'menunggu' => 'Menunggu kaprodi',
        'disetujui_kaprodi' => 'Menunggu LPPM',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
    ];

    /**
     * The authors, creators or inventors, in order.
     *
     * @return HasMany<PublikasiPenulis|HkiPencipta|PatenInventor, $this>
     */
    abstract public function kontributor(): HasMany;

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

    /**
     * Proposal outputs this entry is reported as.
     *
     * @return HasMany<Luaran, $this>
     */
    public function luaran(): HasMany
    {
        return $this->hasMany(Luaran::class);
    }

    /**
     * Entries the lecturer entered or is listed on.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMilikDosen(Builder $query, Dosen $dosen): void
    {
        $query->where(fn (Builder $q) => $q->where('diinput_oleh', $dosen->user_id)
            ->orWhereHas('kontributor', fn (Builder $k) => $k->where('dosen_id', $dosen->id)));
    }

    /**
     * Entries with a lecturer from the head's programme or faculty; a head with
     * no unit set sees them all, as with proposal endorsement.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDalamUnit(Builder $query, User $pimpinan): void
    {
        $peran = $pimpinan->roles->firstWhere('kode', Role::PIMPINAN_UNIT);
        if ($peran === null) {
            $query->whereRaw('1 = 0');
        } elseif ($peran->pivot->prodi_id !== null) {
            $query->whereHas('kontributor.dosen', fn (Builder $q) => $q->where('prodi_id', $peran->pivot->prodi_id));
        } elseif ($peran->pivot->fakultas_id !== null) {
            $query->whereHas('kontributor.dosen.prodi', fn (Builder $q) => $q->where('fakultas_id', $peran->pivot->fakultas_id));
        }
    }

    public function dimilikiOleh(User $user): bool
    {
        return $this->diinput_oleh === $user->id
            || ($user->dosen !== null && $this->kontributor->contains('dosen_id', $user->dosen->id));
    }

    public function namaStatusVerifikasi(): string
    {
        return self::STATUS_VERIFIKASI[$this->status_verifikasi] ?? $this->status_verifikasi;
    }
}
