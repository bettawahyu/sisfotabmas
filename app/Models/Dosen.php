<?php

namespace App\Models;

use App\Enums\JabatanFungsional;
use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'prodi_id', 'nidn', 'nuptk', 'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'jabatan_fungsional', 'sinta_id',
    'jenis_kelamin', 'tempat_lahir', 'tgl_lahir', 'pendidikan_terakhir', 'bidang_keahlian', 'no_hp', 'foto_berkas_id',
    'scopus_author_id', 'google_scholar_id', 'orcid', 'skor_sinta', 'h_index_scopus', 'h_index_scholar',
    'sitasi_scopus', 'sitasi_scholar', 'sinkron_terakhir_at'])]
class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory, SoftDeletes;

    /** Highest degree, lowest first, so a scheme can ask for a minimum. */
    public const PENDIDIKAN = ['S1', 'S2', 'S3'];

    protected $table = 'dosen';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jabatan_fungsional' => JabatanFungsional::class,
            'tgl_lahir' => 'date',
            'skor_sinta' => 'decimal:2',
            'sinkron_terakhir_at' => 'datetime',
        ];
    }

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
     * Lecturers of a faculty or programme head's unit: the programme, every
     * programme of the faculty, or everyone when no unit is set.
     */
    public function scopeDalamUnit(Builder $query, User $pimpinan): void
    {
        $peran = $pimpinan->roles->firstWhere('kode', Role::PIMPINAN_UNIT);
        if ($peran === null) {
            $query->whereRaw('1 = 0');
        } elseif ($peran->pivot->prodi_id !== null) {
            $query->where('prodi_id', $peran->pivot->prodi_id);
        } elseif ($peran->pivot->fakultas_id !== null) {
            $query->whereHas('prodi', fn (Builder $q) => $q->where('fakultas_id', $peran->pivot->fakultas_id));
        }
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
     * Whether the highest degree reaches the given one, such as S2.
     */
    public function pendidikanSetidaknya(string $minimal): bool
    {
        $punya = array_search($this->pendidikan_terakhir, self::PENDIDIKAN, true);

        return $punya !== false && $punya >= array_search($minimal, self::PENDIDIKAN, true);
    }

    /**
     * Set the highest degree from the education history, so scheme requirements
     * follow what the lecturer recorded there.
     */
    public function perbaruiPendidikanTerakhir(): void
    {
        $tertinggi = $this->riwayatPendidikan()->pluck('jenjang')
            ->sortBy(fn (string $jenjang) => array_search($jenjang, self::PENDIDIKAN, true))
            ->last();

        $this->update(['pendidikan_terakhir' => $tertinggi]);
    }

    /**
     * Whether the national identifiers the grant rules require are filled in.
     */
    public function identitasLengkap(): bool
    {
        return ($this->nidn || $this->nuptk) && $this->sinta_id;
    }

    /**
     * @return HasMany<RiwayatPendidikan, $this>
     */
    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(RiwayatPendidikan::class)->orderBy('tahun_lulus');
    }

    /**
     * @return BelongsToMany<Publikasi, $this>
     */
    public function publikasi(): BelongsToMany
    {
        return $this->belongsToMany(Publikasi::class, 'publikasi_penulis');
    }

    /**
     * @return BelongsToMany<Hki, $this>
     */
    public function hki(): BelongsToMany
    {
        return $this->belongsToMany(Hki::class, 'hki_pencipta');
    }

    /**
     * @return BelongsToMany<Paten, $this>
     */
    public function paten(): BelongsToMany
    {
        return $this->belongsToMany(Paten::class, 'paten_inventor');
    }
}
