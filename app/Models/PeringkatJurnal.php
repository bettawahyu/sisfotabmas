<?php

namespace App\Models;

use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Journal or proceedings rank a publication is filed under, such as SINTA 2 or Scopus Q1.
 */
#[Fillable(['kode', 'nama', 'urutan', 'is_active'])]
class PeringkatJurnal extends Model
{
    use BisaNonaktif;

    protected $table = 'peringkat_jurnal';

    /**
     * @return HasMany<Publikasi, $this>
     */
    public function publikasi(): HasMany
    {
        return $this->hasMany(Publikasi::class, 'peringkat', 'kode');
    }

    /**
     * @param  Builder<PeringkatJurnal>  $query
     */
    public function scopeBerurutan(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('nama');
    }
}
