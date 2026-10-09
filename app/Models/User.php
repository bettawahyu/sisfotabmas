<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
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
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
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
}
