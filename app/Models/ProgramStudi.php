<?php

namespace App\Models;

use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['fakultas_id', 'kode', 'nama', 'jenjang', 'is_active'])]
class ProgramStudi extends Model
{
    use BisaNonaktif;

    public const JENJANG = ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi'];

    protected $table = 'program_studi';

    /**
     * @return BelongsTo<Fakultas, $this>
     */
    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class);
    }

    /**
     * @return HasMany<Dosen, $this>
     */
    public function dosen(): HasMany
    {
        return $this->hasMany(Dosen::class, 'prodi_id');
    }
}
