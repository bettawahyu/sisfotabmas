<?php

namespace App\Models;

use App\Enums\JenisKegiatan;
use App\Enums\SumberDana;
use Database\Factories\SkemaHibahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'nama', 'jenis_kegiatan', 'sumber_dana', 'is_multitahun', 'lama_maks_tahun', 'dana_maksimal_default', 'tkt_min', 'honor_diizinkan', 'batas_honor_persen', 'luaran_wajib', 'is_active'])]
class SkemaHibah extends Model
{
    /** @use HasFactory<SkemaHibahFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'skema_hibah';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kegiatan' => JenisKegiatan::class,
            'sumber_dana' => SumberDana::class,
            'is_multitahun' => 'boolean',
            'honor_diizinkan' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PeriodeHibah, $this>
     */
    public function periode(): HasMany
    {
        return $this->hasMany(PeriodeHibah::class, 'skema_id');
    }
}
