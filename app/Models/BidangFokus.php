<?php

namespace App\Models;

use App\Enums\JenisKegiatan;
use App\Models\Concerns\BisaNonaktif;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['jenis_kegiatan', 'kode', 'nama', 'induk_id', 'is_active'])]
class BidangFokus extends Model
{
    use BisaNonaktif;

    protected $table = 'bidang_fokus';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kegiatan' => JenisKegiatan::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BidangFokus, $this>
     */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposal(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
