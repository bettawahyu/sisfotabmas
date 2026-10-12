<?php

namespace App\Models;

use Database\Factories\PengumumanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A news item from LPPM. It shows between terbit_at and berakhir_at to the
 * users holding target_peran, or to everyone when that is 'semua'.
 */
#[Fillable(['judul', 'slug', 'isi', 'kategori', 'periode_id', 'target_peran', 'is_pinned', 'terbit_at', 'berakhir_at', 'dibuat_oleh'])]
class Pengumuman extends Model
{
    /** @use HasFactory<PengumumanFactory> */
    use HasFactory, SoftDeletes;

    public const UMUM = 'umum';

    public const HIBAH = 'hibah';

    public const HASIL = 'hasil';

    /**
     * @var array<string, string>
     */
    public const KATEGORI = [
        self::UMUM => 'Umum',
        self::HIBAH => 'Hibah',
        self::HASIL => 'Hasil seleksi',
    ];

    public const SEMUA = 'semua';

    protected $table = 'pengumuman';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'target_peran' => self::SEMUA,
        'is_pinned' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'terbit_at' => 'datetime',
            'berakhir_at' => 'datetime',
            'notifikasi_terkirim_at' => 'datetime',
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
     * The attached PDF, if any.
     *
     * @return MorphOne<Berkas, $this>
     */
    public function lampiran(): MorphOne
    {
        return $this->morphOne(Berkas::class, 'pemilik')->where('kategori', Berkas::LAMPIRAN)->latestOfMany();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * A slug from the title that no other announcement uses, deleted ones included.
     */
    public static function slugBaru(string $judul): string
    {
        $dasar = Str::limit(Str::slug($judul), 80, '') ?: 'pengumuman';
        $slug = $dasar;
        for ($i = 2; static::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }

    public function sedangTampil(): bool
    {
        return $this->terbit_at !== null && $this->terbit_at->isPast()
            && ($this->berakhir_at === null || $this->berakhir_at->isFuture());
    }

    public function untukSemua(): bool
    {
        return $this->target_peran === self::SEMUA;
    }

    /**
     * Active users the announcement is meant for.
     *
     * @return Builder<User>
     */
    public function penerima(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->when(
                $this->untukSemua(),
                fn (Builder $q) => $q->whereHas('roles'),
                fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->where('kode', $this->target_peran)),
            );
    }

    /**
     * Announcements this user may read: meant for everyone or for one of their roles.
     *
     * @param  Builder<Pengumuman>  $query
     */
    public function scopeUntuk(Builder $query, User $user): void
    {
        $query->whereIn('target_peran', [self::SEMUA, ...$user->roles->pluck('kode')]);
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
