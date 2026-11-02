<?php

namespace App\Models;

use App\Enums\JenisKegiatan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Research or community service a lecturer did outside the LPPM grant cycle:
 * self-funded, or funded by the ministry, a local government, industry and
 * the like. A lecturer's entry waits for LPPM or an operator; one LPPM or an
 * operator enters is valid at once.
 */
#[Fillable(['jenis_kegiatan', 'judul', 'tahun_mulai', 'tahun_selesai', 'status_kegiatan', 'sumber_dana', 'pemberi_dana', 'skema',
    'nilai_dana', 'no_kontrak', 'bidang_fokus_id', 'kabupaten_kota_kode', 'alamat_lokasi', 'ringkasan',
    'jumlah_sasaran', 'catatan_dampak', 'tautan_video', 'tautan_media'])]
class RiwayatKegiatan extends Model
{
    use SoftDeletes;

    public const BERJALAN = 'berjalan';

    public const SELESAI = 'selesai';

    public const STATUS_KEGIATAN = [
        'berjalan' => 'Sedang berlangsung',
        'selesai' => 'Selesai',
    ];

    public const SUMBER_DANA = [
        'mandiri' => 'Mandiri',
        'kemdiktisaintek' => 'Kemdiktisaintek (DRTPM/BIMA)',
        'pemerintah_daerah' => 'Pemerintah daerah',
        'kementerian_lain' => 'Kementerian atau lembaga lain',
        'industri' => 'Industri atau swasta',
        'perguruan_tinggi' => 'Perguruan tinggi lain',
        'luar_negeri' => 'Lembaga luar negeri',
        'lainnya' => 'Lainnya',
    ];

    public const KETUA = 'ketua';

    public const ANGGOTA = 'anggota';

    public const MENUNGGU = 'menunggu';

    public const VALID = 'valid';

    public const DITOLAK = 'ditolak';

    public const STATUS_VERIFIKASI = [
        'menunggu' => 'Menunggu verifikasi',
        'valid' => 'Valid',
        'ditolak' => 'Ditolak',
    ];

    /** Berkas categories on a record. */
    public const BERKAS_BUKTI = 'bukti_kegiatan';

    public const BERKAS_LAPORAN = 'laporan_kegiatan';

    public const BERKAS_SURAT_MITRA = Berkas::SURAT_KETERANGAN_MITRA;

    public const BERKAS_FOTO = Berkas::FOTO_DOKUMENTASI;

    protected $table = 'riwayat_kegiatan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kegiatan' => JenisKegiatan::class,
            'diverifikasi_lppm_at' => 'datetime',
        ];
    }

    /**
     * Lecturers, students and outsiders in order; the first is usually the lead.
     *
     * @return HasMany<RiwayatKegiatanAnggota, $this>
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(RiwayatKegiatanAnggota::class)->orderBy('urutan');
    }

    /**
     * @return BelongsToMany<Mitra, $this>
     */
    public function mitra(): BelongsToMany
    {
        return $this->belongsToMany(Mitra::class, 'riwayat_kegiatan_mitra')->orderBy('nama');
    }

    /**
     * Outputs the record is reported with, from the lecturers' Publikasi & KI.
     *
     * @return BelongsToMany<Publikasi, $this>
     */
    public function publikasi(): BelongsToMany
    {
        return $this->belongsToMany(Publikasi::class, 'riwayat_kegiatan_luaran', 'riwayat_kegiatan_id', 'karya_id')->withPivotValue('jenis_karya', 'publikasi');
    }

    /**
     * @return BelongsToMany<Hki, $this>
     */
    public function hki(): BelongsToMany
    {
        return $this->belongsToMany(Hki::class, 'riwayat_kegiatan_luaran', 'riwayat_kegiatan_id', 'karya_id')->withPivotValue('jenis_karya', 'hki');
    }

    /**
     * @return BelongsToMany<Paten, $this>
     */
    public function paten(): BelongsToMany
    {
        return $this->belongsToMany(Paten::class, 'riwayat_kegiatan_luaran', 'riwayat_kegiatan_id', 'karya_id')->withPivotValue('jenis_karya', 'paten');
    }

    /**
     * @return Collection<int, Publikasi|Hki|Paten>
     */
    public function karyaTertaut(): Collection
    {
        return collect([...$this->publikasi, ...$this->hki, ...$this->paten]);
    }

    /**
     * @return BelongsTo<KabupatenKota, $this>
     */
    public function kabupatenKota(): BelongsTo
    {
        return $this->belongsTo(KabupatenKota::class, 'kabupaten_kota_kode');
    }

    /**
     * @return BelongsTo<BidangFokus, $this>
     */
    public function bidangFokus(): BelongsTo
    {
        return $this->belongsTo(BidangFokus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diinput_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikatorLppm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_lppm_oleh');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * @param  Builder<RiwayatKegiatan>  $query
     */
    public function scopeJenis(Builder $query, JenisKegiatan $jenis): void
    {
        $query->where('jenis_kegiatan', $jenis);
    }

    /**
     * Records the lecturer is listed on, whoever entered them.
     *
     * @param  Builder<RiwayatKegiatan>  $query
     */
    public function scopeMilikDosen(Builder $query, Dosen $dosen): void
    {
        $query->whereHas('anggota', fn (Builder $a) => $a->where('dosen_id', $dosen->id));
    }

    /**
     * Records with a lecturer from the head's programme or faculty.
     *
     * @param  Builder<RiwayatKegiatan>  $query
     */
    public function scopeDalamUnit(Builder $query, User $pimpinan): void
    {
        $query->whereHas('anggota.dosen', fn (Builder $d) => $d->dalamUnit($pimpinan));
    }

    public function dicantumkan(Dosen $dosen): bool
    {
        return $this->anggota->contains('dosen_id', $dosen->id);
    }

    public function ketua(): ?RiwayatKegiatanAnggota
    {
        return $this->anggota->firstWhere('peran', self::KETUA);
    }

    public function isPkm(): bool
    {
        return $this->jenis_kegiatan === JenisKegiatan::Pkm;
    }

    public function periode(): string
    {
        return $this->tahun_selesai && $this->tahun_selesai !== $this->tahun_mulai
            ? "{$this->tahun_mulai}–{$this->tahun_selesai}"
            : (string) $this->tahun_mulai;
    }

    /**
     * Press coverage, one link per line.
     *
     * @return list<string>
     */
    public function daftarTautanMedia(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->tautan_media))));
    }

    public function namaSumberDana(): string
    {
        return self::SUMBER_DANA[$this->sumber_dana] ?? $this->sumber_dana;
    }

    public function namaStatusKegiatan(): string
    {
        return self::STATUS_KEGIATAN[$this->status_kegiatan] ?? $this->status_kegiatan;
    }

    public function namaStatusVerifikasi(): string
    {
        return self::STATUS_VERIFIKASI[$this->status_verifikasi] ?? $this->status_verifikasi;
    }

    /**
     * Whether the final report file is in; records imported from Excel come
     * without one and are completed later through the form.
     */
    public function laporanTerunggah(): bool
    {
        return $this->berkas->contains('kategori', self::BERKAS_LAPORAN);
    }

    public function namaStatusLaporan(): string
    {
        return $this->laporanTerunggah() ? 'Sudah unggah' : 'Belum unggah';
    }
}
