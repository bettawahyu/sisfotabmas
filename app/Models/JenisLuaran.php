<?php

namespace App\Models;

use App\Enums\JenisKegiatan;
use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of research or community-service output, such as a journal article or a
 * video. Outputs store its code, so the name can change without touching them.
 */
#[Fillable(['kode', 'nama', 'berlaku_untuk', 'tautan_karya', 'urutan', 'is_active'])]
class JenisLuaran extends Model
{
    use BisaNonaktif;

    public const SEMUA = 'semua';

    public const BERLAKU = [
        'semua' => 'Penelitian dan pengabdian',
        'penelitian' => 'Penelitian saja',
        'pkm' => 'Pengabdian saja',
    ];

    /** The page an achieved output of this type can be linked to. */
    public const TAUTAN = [
        'publikasi' => Publikasi::class,
        'hki' => Hki::class,
        'paten' => Paten::class,
    ];

    public const NAMA_TAUTAN = [
        'publikasi' => 'Publikasi',
        'hki' => 'HKI',
        'paten' => 'Paten',
    ];

    protected $table = 'jenis_luaran';

    /**
     * @return HasMany<Luaran, $this>
     */
    public function luaran(): HasMany
    {
        return $this->hasMany(Luaran::class, 'jenis', 'kode');
    }

    /**
     * @return class-string<Publikasi|Hki|Paten>|null
     */
    public function modelKarya(): ?string
    {
        return self::TAUTAN[$this->tautan_karya] ?? null;
    }

    public function berlakuUntuk(JenisKegiatan $jenis): bool
    {
        return $this->berlaku_untuk === self::SEMUA || $this->berlaku_untuk === $jenis->value;
    }

    /**
     * @param  Builder<JenisLuaran>  $query
     */
    public function scopeUntukKegiatan(Builder $query, JenisKegiatan $jenis): void
    {
        $query->whereIn('berlaku_untuk', [self::SEMUA, $jenis->value]);
    }

    /**
     * @param  Builder<JenisLuaran>  $query
     */
    public function scopeBerurutan(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('nama');
    }
}
