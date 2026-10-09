<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['kode', 'nama'])]
class Role extends Model
{
    public const DOSEN = 'dosen';

    public const REVIEWER = 'reviewer';

    public const PIMPINAN_UNIT = 'pimpinan_unit';

    public const ADMIN_LPPM = 'admin_lppm';

    public const PIMPINAN = 'pimpinan';

    public const KEUANGAN = 'keuangan';

    /**
     * Every role the application knows about, keyed by kode.
     *
     * @var array<string, string>
     */
    public const ALL = [
        self::DOSEN => 'Dosen',
        self::REVIEWER => 'Reviewer',
        self::PIMPINAN_UNIT => 'Pimpinan Unit',
        self::ADMIN_LPPM => 'Admin LPPM',
        self::PIMPINAN => 'Pimpinan',
        self::KEUANGAN => 'Keuangan',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }
}
