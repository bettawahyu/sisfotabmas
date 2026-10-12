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
}
