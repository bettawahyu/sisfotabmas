<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kategori', 'nama_asli', 'path', 'mime', 'ukuran_byte', 'diunggah_oleh'])]
class Berkas extends Model
{
    use SoftDeletes;

    public const SUBSTANSI = 'substansi';

    public const SURAT_MITRA = 'surat_mitra';

    public const LAMPIRAN = 'lampiran';

    public const GAMBAR_UNGGULAN = 'gambar_unggulan';

    public const TEMPLAT = 'templat';

    public const BUKTI_PENCAIRAN = 'bukti_pencairan';

    public const LAPORAN_KEMAJUAN = 'laporan_kemajuan';

    public const LAPORAN_KEUANGAN = 'laporan_keuangan';

    public const BUKTI_LOGBOOK = 'bukti_logbook';

    public const LAPORAN_AKHIR = 'laporan_akhir';

    /** Community service evidence, on a final report or a record outside the grants. */
    public const FOTO_DOKUMENTASI = 'foto_dokumentasi';

    public const SURAT_KETERANGAN_MITRA = 'surat_keterangan_mitra';

    /** How a report's files are named on screen. */
    public const LABEL = [
        'laporan_akhir' => 'Laporan akhir',
        'laporan_keuangan' => 'Laporan keuangan',
        'laporan_kemajuan' => 'Laporan kemajuan',
        'foto_dokumentasi' => 'Foto dokumentasi',
        'surat_keterangan_mitra' => 'Surat keterangan mitra',
    ];

    protected $table = 'berkas';

    /**
     * @return MorphTo<Model, $this>
     */
    public function pemilik(): MorphTo
    {
        return $this->morphTo();
    }

    public function namaKategori(): string
    {
        return self::LABEL[$this->kategori] ?? $this->kategori;
    }
}
