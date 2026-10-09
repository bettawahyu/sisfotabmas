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

    protected $table = 'berkas';

    /**
     * @return MorphTo<Model, $this>
     */
    public function pemilik(): MorphTo
    {
        return $this->morphTo();
    }
}
