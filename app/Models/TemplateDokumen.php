<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A template file for proposals and reports; a null skema or tahun applies to all.
 */
#[Fillable(['jenis', 'skema_id', 'tahun', 'nama', 'versi', 'is_active', 'diunggah_oleh'])]
class TemplateDokumen extends Model
{
    use SoftDeletes;

    public const SUBSTANSI = 'substansi';

    public const LAPORAN_KEMAJUAN = 'laporan_kemajuan';

    public const LAPORAN_KEUANGAN = 'laporan_keuangan';

    public const LAPORAN_AKHIR = 'laporan_akhir';

    public const LOGBOOK = 'logbook';

    /**
     * @var array<string, string>
     */
    public const JENIS = [
        self::SUBSTANSI => 'Substansi proposal',
        self::LAPORAN_KEMAJUAN => 'Laporan kemajuan',
        self::LAPORAN_KEUANGAN => 'Laporan penggunaan anggaran',
        self::LAPORAN_AKHIR => 'Laporan akhir',
        self::LOGBOOK => 'Catatan harian (logbook)',
    ];

    protected $table = 'template_dokumen';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SkemaHibah, $this>
     */
    public function skema(): BelongsTo
    {
        return $this->belongsTo(SkemaHibah::class, 'skema_id');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * The latest uploaded file of this template.
     *
     * @return MorphOne<Berkas, $this>
     */
    public function berkasTerbaru(): MorphOne
    {
        return $this->morphOne(Berkas::class, 'pemilik')->where('kategori', Berkas::TEMPLAT)->latestOfMany();
    }

    public function namaJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    /**
     * Active templates that apply to the given scheme and year: those made for it
     * and those that leave skema or tahun empty.
     *
     * @param  Builder<TemplateDokumen>  $query
     */
    public function scopeBerlakuUntuk(Builder $query, ?int $skemaId, ?int $tahun): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('skema_id')->orWhere('skema_id', $skemaId))
            ->where(fn (Builder $q) => $q->whereNull('tahun')->orWhere('tahun', $tahun))
            ->orderBy('jenis')
            ->orderBy('nama');
    }
}
