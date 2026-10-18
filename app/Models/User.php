<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot('fakultas_id', 'prodi_id')->withTimestamps();
    }

    /**
     * Determine whether the user holds any of the given role codes.
     */
    public function hasRole(string ...$kode): bool
    {
        return $this->roles->whereIn('kode', $kode)->isNotEmpty();
    }

    /**
     * Give the user the given role codes, keeping the roles they already have.
     */
    public function assignRole(string ...$kode): void
    {
        $this->roles()->syncWithoutDetaching(Role::whereIn('kode', $kode)->pluck('id'));
        $this->unsetRelation('roles');
    }

    /**
     * The lecturer profile linked to this account, if any.
     *
     * @return HasOne<Dosen, $this>
     */
    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class);
    }

    /**
     * Reviews assigned to this user as a reviewer.
     *
     * @return HasMany<Penilaian, $this>
     */
    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'reviewer_id');
    }

    /**
     * Up to two initials from the user's name, shown when there is no profile photo.
     */
    public function inisial(): string
    {
        return Str::of($this->name)->squish()->explode(' ')
            ->filter()->take(2)
            ->map(fn (string $kata) => Str::upper(Str::substr($kata, 0, 1)))
            ->implode('') ?: '?';
    }

    /**
     * Whether this user may endorse the proposal: a faculty or programme head
     * whose unit is the lead's programme or its faculty, or who has no unit set.
     */
    public function bolehMengesahkan(Proposal $proposal): bool
    {
        $peran = $this->roles->firstWhere('kode', Role::PIMPINAN_UNIT);
        if ($peran === null) {
            return false;
        }

        $prodi = $proposal->ketua?->prodi;

        return match (true) {
            $peran->pivot->prodi_id !== null => $prodi?->id === $peran->pivot->prodi_id,
            $peran->pivot->fakultas_id !== null => $prodi?->fakultas_id === $peran->pivot->fakultas_id,
            default => true,
        };
    }
}
