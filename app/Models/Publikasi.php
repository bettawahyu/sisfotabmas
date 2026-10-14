<?php

namespace App\Models;

use App\Models\Concerns\MemilikiVerifikasi;
use Database\Factories\PublikasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public const PERINGKAT = [
        'scopus_q1' => 'Scopus Q1',
        'scopus_q2' => 'Scopus Q2',
        'scopus_q3' => 'Scopus Q3',
        'scopus_q4' => 'Scopus Q4',
        'sinta_1' => 'SINTA 1',
        'sinta_2' => 'SINTA 2',
        'sinta_3' => 'SINTA 3',
        'sinta_4' => 'SINTA 4',
        'sinta_5' => 'SINTA 5',
        'sinta_6' => 'SINTA 6',
        'lainnya' => 'Lainnya',
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

    public function namaJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function namaStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
