<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama'])]
class Fakultas extends Model
{
    protected $table = 'fakultas';

    /**
     * @return HasMany<ProgramStudi, $this>
     */
    public function programStudi(): HasMany
    {
        return $this->hasMany(ProgramStudi::class);
    }
}
