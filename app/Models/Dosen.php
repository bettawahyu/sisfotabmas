<?php

namespace App\Models;

use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'prodi_id', 'nidn', 'nuptk', 'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'jabatan_fungsional', 'sinta_id'])]
class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'dosen';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ProgramStudi, $this>
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposalSebagaiKetua(): HasMany
    {
        return $this->hasMany(Proposal::class, 'ketua_id');
    }

    public function namaLengkap(): string
    {
        return trim(collect([$this->gelar_depan, $this->nama])->filter()->implode(' ')
            .($this->gelar_belakang ? ', '.$this->gelar_belakang : ''));
    }

    /**
     * Whether the national identifiers the grant rules require are filled in.
     */
    public function identitasLengkap(): bool
    {
        return ($this->nidn || $this->nuptk) && $this->sinta_id;
    }
}
