<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kategori', 'jenis', 'target_keterangan', 'status_capaian', 'url_bukti', 'publikasi_id', 'hki_id', 'paten_id'])]
class Luaran extends Model
{
    public const MENUNGGU = 'menunggu';

    public const VALID = 'valid';

    public const DITOLAK = 'ditolak';

    public const DIRENCANAKAN = 'direncanakan';

    /** How far the lead says the output has come; anything past the plan counts as in process. */
    public const CAPAIAN = [
        'direncanakan' => 'Direncanakan',
        'draf' => 'Draf',
        'submitted' => 'Sudah dikirim (submitted)',
        'diterima' => 'Diterima (accepted)',
        'terbit' => 'Terbit atau granted',
    ];

    public const VALIDASI = [
        'menunggu' => 'Menunggu validasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
    ];

    protected $table = 'luaran';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<JenisLuaran, $this>
     */
    public function jenisLuaran(): BelongsTo
    {
        return $this->belongsTo(JenisLuaran::class, 'jenis', 'kode');
    }

    public function namaJenis(): string
    {
        return $this->jenisLuaran?->nama ?? $this->jenis;
    }

    /**
     * The kind of entry on the publication, HKI and patent pages this output
     * can be linked to, if any.
     *
     * @return class-string<Publikasi|Hki|Paten>|null
     */
    public function modelKarya(): ?string
    {
        return $this->jenisLuaran?->modelKarya();
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
     * The publication, HKI or patent this output is reported as, if linked.
     */
    public function karya(): Publikasi|Hki|Paten|null
    {
        return $this->publikasi ?? $this->hki ?? $this->paten;
    }

    public function namaCapaian(): string
    {
        return self::CAPAIAN[$this->status_capaian] ?? $this->status_capaian;
    }

    /**
     * Validated by the operator and at least sent to a journal or office, which
     * is what the second tranche asks of each required output.
     */
    public function memenuhiTermin2(): bool
    {
        return $this->status_validasi === self::VALID
            && in_array($this->status_capaian, ['submitted', 'diterima', 'terbit'], true);
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
