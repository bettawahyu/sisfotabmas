<?php

namespace App\Models;

use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'is_active'])]
class Fakultas extends Model
{
    use BisaNonaktif;

    protected $table = 'fakultas';

    /**
     * @return HasMany<ProgramStudi, $this>
     */
    public function programStudi(): HasMany
    {
        return $this->hasMany(ProgramStudi::class);
    }
}
