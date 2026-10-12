<?php

namespace App\Models;

use Database\Factories\LaporanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A progress, spending or final report.
 */
#[Fillable(['jenis', 'ringkasan', 'persentase_capaian', 'persentase_anggaran_terpakai', 'status', 'diajukan_at', 'diverifikasi_oleh', 'diverifikasi_at'])]
class Laporan extends Model
{
    /** @use HasFactory<LaporanFactory> */
    use HasFactory;

    public const KEMAJUAN = 'kemajuan';

    public const KEUANGAN = 'keuangan';

    public const AKHIR = 'akhir';

    protected $table = 'laporan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diajukan_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /**
     * @return HasMany<Penilaian, $this>
     */
    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class);
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }
}
