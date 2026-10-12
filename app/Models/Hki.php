<?php

namespace App\Models;

use App\Models\Concerns\MemilikiVerifikasi;
use Database\Factories\HkiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hak cipta, merek, desain industri and other HKI except patents.
 */
#[Fillable(['jenis', 'jenis_ciptaan', 'judul', 'uraian', 'pemegang_hak', 'no_permohonan', 'tgl_permohonan', 'no_pencatatan', 'tgl_pencatatan', 'masa_berlaku_sampai', 'status', 'url_pdki', 'diinput_oleh'])]
class Hki extends Model
{
    /** @use HasFactory<HkiFactory> */
    use HasFactory, MemilikiVerifikasi, SoftDeletes;

    protected $table = 'hki';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_permohonan' => 'date',
            'tgl_pencatatan' => 'date',
            'masa_berlaku_sampai' => 'date',
        ];
    }

    /**
     * @return HasMany<HkiPencipta, $this>
     */
    public function pencipta(): HasMany
    {
        return $this->hasMany(HkiPencipta::class)->orderBy('urutan');
    }
}
