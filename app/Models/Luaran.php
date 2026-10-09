<?php

namespace App\Models;

use App\Enums\JenisLuaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kategori', 'jenis', 'target_keterangan', 'status_capaian', 'url_bukti'])]
class Luaran extends Model
{
    protected $table = 'luaran';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisLuaran::class,
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
