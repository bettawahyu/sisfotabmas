<?php

namespace App\Models;

use App\Models\Concerns\MemilikiVerifikasi;
use Database\Factories\PatenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A patent or simple patent, tracked through the DJKI examination stages.
 */
#[Fillable(['jenis', 'judul', 'abstrak', 'pemegang_paten', 'konsultan_ki', 'no_permohonan', 'tgl_penerimaan', 'tgl_publikasi', 'klasifikasi_ipc', 'jumlah_klaim', 'no_paten', 'tgl_pemberian', 'masa_berlaku_sampai', 'status', 'url_pdki', 'diinput_oleh'])]
class Paten extends Model
{
    /** @use HasFactory<PatenFactory> */
    use HasFactory, MemilikiVerifikasi, SoftDeletes;

    protected $table = 'paten';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_penerimaan' => 'date',
            'tgl_publikasi' => 'date',
            'tgl_pemberian' => 'date',
            'masa_berlaku_sampai' => 'date',
        ];
    }

    /**
     * @return HasMany<PatenInventor, $this>
     */
    public function inventor(): HasMany
    {
        return $this->hasMany(PatenInventor::class)->orderBy('urutan');
    }
}
