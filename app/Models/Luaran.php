<?php

namespace App\Models;

use App\Enums\JenisLuaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kategori', 'jenis', 'target_keterangan', 'status_capaian', 'url_bukti', 'publikasi_id', 'hki_id', 'paten_id'])]
class Luaran extends Model
{
    public const MENUNGGU = 'menunggu';

    public const VALID = 'valid';

    public const DITOLAK = 'ditolak';

    protected $table = 'luaran';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisLuaran::class,
            'divalidasi_at' => 'datetime',
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
     * @return BelongsTo<Publikasi, $this>
     */
    public function publikasi(): BelongsTo
    {
        return $this->belongsTo(Publikasi::class);
    }

    /**
     * @return BelongsTo<Hki, $this>
     */
    public function hki(): BelongsTo
    {
        return $this->belongsTo(Hki::class);
    }

    /**
     * @return BelongsTo<Paten, $this>
     */
    public function paten(): BelongsTo
    {
        return $this->belongsTo(Paten::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }

    /**
     * The operator validates an output, recording who did it and when.
     */
    public function validasi(User $oleh, bool $valid, ?string $catatan = null): void
    {
        $this->forceFill([
            'status_validasi' => $valid ? self::VALID : self::DITOLAK,
            'divalidasi_oleh' => $oleh->id,
            'divalidasi_at' => now(),
            'catatan_validasi' => $catatan,
        ])->save();
    }
}
