<?php

namespace App\Models;

use Database\Factories\PenilaianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One reviewer's assessment of a proposal at one stage: selection, monev or final.
 */
#[Fillable(['laporan_id', 'reviewer_id', 'tahap', 'urutan_reviewer', 'status', 'nilai_total', 'rekomendasi', 'komentar_untuk_pengusul', 'catatan_internal', 'rekomendasi_dana', 'dinilai_at'])]
class Penilaian extends Model
{
    /** @use HasFactory<PenilaianFactory> */
    use HasFactory;

    public const SELEKSI = 'seleksi';

    public const PRESENTASI = 'presentasi';

    public const MONEV = 'monev';

    public const AKHIR = 'akhir';

    public const DITUGASKAN = 'ditugaskan';

    public const SELESAI = 'selesai';

    /**
     * Each selection proposal gets exactly two reviewers; there is no third.
     */
    public const JUMLAH_REVIEWER = 2;

    /**
     * @var array<string, string>
     */
    public const REKOMENDASI = [
        'didanai' => 'Layak didanai',
        'didanai_perbaikan' => 'Layak didanai dengan perbaikan',
        'tidak_didanai' => 'Tidak layak didanai',
    ];

    protected $table = 'penilaian';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai_total' => 'decimal:2',
            'dinilai_at' => 'datetime',
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
     * @return BelongsTo<Laporan, $this>
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return HasMany<NilaiPenilaian, $this>
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(NilaiPenilaian::class);
    }

    /**
     * Sent by the reviewer, so locked until LPPM reopens it.
     */
    public function terkirim(): bool
    {
        return $this->status === self::SELESAI;
    }

    public function namaRekomendasi(): ?string
    {
        return $this->rekomendasi ? (self::REKOMENDASI[$this->rekomendasi] ?? $this->rekomendasi) : null;
    }
}
