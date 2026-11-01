<?php

namespace App\Models;

use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A community service or research partner. LPPM keeps the register; a partner
 * a lecturer adds is proposed and used on their record at once, and LPPM
 * decides whether it joins the register everyone picks from.
 */
#[Fillable(['nama', 'bidang', 'alamat', 'kontak', 'kabupaten_kota_kode', 'is_active'])]
class Mitra extends Model
{
    use BisaNonaktif;

    public const USULAN = 'usulan';

    public const MASTER = 'master';

    public const DITOLAK = 'ditolak';

    public const STATUS = [
        'usulan' => 'Usulan dosen',
        'master' => 'Masuk data master',
        'ditolak' => 'Tidak dimasukkan',
    ];

    protected $table = 'mitra';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diverifikasi_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KabupatenKota, $this>
     */
    public function kabupatenKota(): BelongsTo
    {
        return $this->belongsTo(KabupatenKota::class, 'kabupaten_kota_kode');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengusul(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diusulkan_oleh');
    }

    /**
     * @return BelongsToMany<RiwayatKegiatan, $this>
     */
    public function riwayatKegiatan(): BelongsToMany
    {
        return $this->belongsToMany(RiwayatKegiatan::class, 'riwayat_kegiatan_mitra');
    }

    /**
     * Partners a form may offer the user: the active register, plus what they
     * proposed themselves and the ones the record already has.
     *
     * @param  Builder<Mitra>  $query
     * @param  list<int>  $terpilih
     */
    public function scopeBisaDipilih(Builder $query, User $user, array $terpilih = []): void
    {
        $query->where(fn (Builder $q) => $q->where(fn (Builder $m) => $m->where('status', self::MASTER)->where('is_active', true))
            ->orWhere(fn (Builder $u) => $u->where('status', self::USULAN)->where('diusulkan_oleh', $user->id))
            ->orWhereIn('id', $terpilih ?: [0]));
    }

    public function namaStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
