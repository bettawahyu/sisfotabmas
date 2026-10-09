<?php

namespace App\Models;

use App\Enums\KategoriRab;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tahun_ke', 'kategori', 'uraian', 'volume', 'satuan', 'harga_satuan', 'total'])]
class RabItem extends Model
{
    protected $table = 'rab_item';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kategori' => KategoriRab::class,
            'volume' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
