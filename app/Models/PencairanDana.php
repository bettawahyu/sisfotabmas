<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * One funding tranche of a grant.
 */
#[Fillable(['termin_ke', 'persentase', 'jumlah', 'tgl_cair', 'syarat', 'pajak_lunas', 'ditahan_karena_monev', 'dicairkan_oleh'])]
class PencairanDana extends Model
{
    public const SYARAT_KONTRAK = 'kontrak';

    public const SYARAT_LAPORAN_AKHIR = 'laporan_akhir';

    protected $table = 'pencairan_dana';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_cair' => 'date',
            'pajak_lunas' => 'boolean',
            'ditahan_karena_monev' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pencair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicairkan_oleh');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * @return MorphOne<Berkas, $this>
     */
    public function bukti(): MorphOne
    {
        return $this->morphOne(Berkas::class, 'pemilik')->where('kategori', Berkas::BUKTI_PENCAIRAN)->latestOfMany();
    }
}
