<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A province, keyed by its Kemendagri code.
 */
class Provinsi extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'provinsi';

    protected $primaryKey = 'kode';

    protected $keyType = 'string';

    /**
     * @return HasMany<KabupatenKota, $this>
     */
    public function kabupatenKota(): HasMany
    {
        return $this->hasMany(KabupatenKota::class, 'provinsi_kode');
    }
}
