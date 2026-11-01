<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A regency or city, keyed by its Kemendagri code without the dot (1101).
 */
class KabupatenKota extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'kabupaten_kota';

    protected $primaryKey = 'kode';

    protected $keyType = 'string';

    /**
     * @return BelongsTo<Provinsi, $this>
     */
    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_kode');
    }

    public function namaLengkap(): string
    {
        return $this->nama.', '.$this->provinsi->nama;
    }

    /**
     * Provinces with their regencies, for the two linked dropdowns.
     *
     * @return Collection<int, array{kode: string, nama: string, kabupaten: list<array{kode: string, nama: string}>}>
     */
    public static function pilihan(): Collection
    {
        $kabupaten = self::orderBy('kode')->get(['kode', 'provinsi_kode', 'nama'])->groupBy('provinsi_kode');

        return Provinsi::orderBy('nama')->get()->map(fn (Provinsi $p) => [
            'kode' => $p->kode,
            'nama' => $p->nama,
            'kabupaten' => $kabupaten->get($p->kode, collect())->map(fn ($k) => ['kode' => $k->kode, 'nama' => $k->nama])->values()->all(),
        ]);
    }
}
