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

    public const JENIS = [
        'hak_cipta' => 'Hak cipta',
        'merek' => 'Merek',
        'desain_industri' => 'Desain industri',
        'desain_tata_letak_sirkuit' => 'Desain tata letak sirkuit terpadu',
        'rahasia_dagang' => 'Rahasia dagang',
        'varietas_tanaman' => 'Perlindungan varietas tanaman',
    ];

    public const STATUS = [
        'draft' => 'Draf',
        'diajukan' => 'Diajukan',
        'tercatat' => 'Tercatat atau terdaftar',
        'ditolak' => 'Ditolak DJKI',
    ];

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

    /**
     * @return HasMany<HkiPencipta, $this>
     */
    public function kontributor(): HasMany
    {
        return $this->pencipta();
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
