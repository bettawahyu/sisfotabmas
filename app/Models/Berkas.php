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

    public const TEMPLAT = 'templat';

    public const BUKTI_PENCAIRAN = 'bukti_pencairan';

    public const LAPORAN_KEMAJUAN = 'laporan_kemajuan';

    public const LAPORAN_KEUANGAN = 'laporan_keuangan';

    public const BUKTI_LOGBOOK = 'bukti_logbook';

    protected $table = 'berkas';

    /**
     * @return MorphTo<Model, $this>
     */
    public function pemilik(): MorphTo
    {
        return $this->morphTo();
    }
}
