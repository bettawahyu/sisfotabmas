<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Database\Factories\PeriodeHibahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One year's call for proposals under a scheme (grant_calls in the flow spec).
 */
#[Fillable(['skema_id', 'tahun_anggaran', 'nama', 'tgl_buka', 'tgl_tutup', 'tgl_seleksi_selesai', 'tgl_pengumuman', 'tgl_laporan_kemajuan', 'tgl_monev', 'tgl_laporan_akhir', 'kuota', 'dana_maksimal', 'honor_diizinkan', 'batas_honor_persen', 'termin_pertama_persen', 'maks_sebagai_ketua', 'maks_sebagai_anggota', 'status'])]
class PeriodeHibah extends Model
{
    /** @use HasFactory<PeriodeHibahFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'periode_hibah';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_buka' => 'date',
            'tgl_tutup' => 'date',
            'tgl_seleksi_selesai' => 'date',
            'tgl_pengumuman' => 'date',
            'tgl_laporan_kemajuan' => 'date',
            'tgl_monev' => 'date',
            'tgl_laporan_akhir' => 'date',
            'honor_diizinkan' => 'boolean',
            'status' => StatusPeriode::class,
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
     * @return HasMany<Proposal, $this>
     */
    public function proposal(): HasMany
    {
        return $this->hasMany(Proposal::class, 'periode_id');
    }

    /**
     * Calls that are open and inside their submission window today.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSedangDibuka(Builder $query): void
    {
        $query->where('status', StatusPeriode::Dibuka)
            ->whereDate('tgl_buka', '<=', today())
            ->whereDate('tgl_tutup', '>=', today());
    }

    public function menerimaPengajuan(): bool
    {
        return $this->status === StatusPeriode::Dibuka
            && today()->between($this->tgl_buka, $this->tgl_tutup);
    }
}
