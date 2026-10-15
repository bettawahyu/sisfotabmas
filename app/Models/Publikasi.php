<?php

namespace App\Models;

use App\Models\Concerns\MemilikiVerifikasi;
use Database\Factories\PublikasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A journal article, proceeding, book or chapter, with its authors in order.
 */
#[Fillable(['jenis', 'judul', 'abstrak', 'nama_wadah', 'issn_isbn', 'volume', 'nomor', 'halaman', 'tahun', 'tgl_terbit', 'doi', 'url', 'peringkat', 'status', 'diinput_oleh'])]
class Publikasi extends Model
{
    /** @use HasFactory<PublikasiFactory> */
    use HasFactory, MemilikiVerifikasi, SoftDeletes;

    public const JENIS = [
        'jurnal_internasional' => 'Jurnal internasional',
        'jurnal_nasional' => 'Jurnal nasional',
        'prosiding_internasional' => 'Prosiding internasional',
        'prosiding_nasional' => 'Prosiding nasional',
        'buku' => 'Buku',
        'bab_buku' => 'Bab buku',
    ];

    public const STATUS = [
        'draft' => 'Draf',
        'submitted' => 'Dikirim (submitted)',
        'accepted' => 'Diterima (accepted)',
        'published' => 'Terbit',
    ];

    protected $table = 'publikasi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'date',
        ];
    }

    /**
     * @return HasMany<PublikasiPenulis, $this>
     */
    public function penulis(): HasMany
    {
        return $this->hasMany(PublikasiPenulis::class)->orderBy('urutan');
    }

    /**
     * @return HasMany<PublikasiPenulis, $this>
     */
    public function kontributor(): HasMany
    {
        return $this->penulis();
    }

    /**
     * The rank is stored by its code so ranks can be renamed without touching publications.
     *
     * @return BelongsTo<PeringkatJurnal, $this>
     */
    public function peringkatJurnal(): BelongsTo
    {
        return $this->belongsTo(PeringkatJurnal::class, 'peringkat', 'kode');
    }

    public function namaPeringkat(): ?string
    {
        return $this->peringkat ? ($this->peringkatJurnal?->nama ?? $this->peringkat) : null;
    }

    public function namaJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function namaStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
