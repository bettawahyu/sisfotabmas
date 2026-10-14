<?php

namespace App\Enums;

use App\Models\Hki;
use App\Models\Paten;
use App\Models\Publikasi;

enum JenisLuaran: string
{
    case ArtikelJurnal = 'artikel_jurnal';
    case Prosiding = 'prosiding';
    case Hki = 'hki';
    case Paten = 'paten';
    case Buku = 'buku';
    case TeknologiTepatGuna = 'teknologi_tepat_guna';
    case VideoKegiatan = 'video_kegiatan';
    case PublikasiMedia = 'publikasi_media';
    case TestimoniMitra = 'testimoni_mitra';

    public function label(): string
    {
        return match ($this) {
            self::ArtikelJurnal => 'Artikel jurnal',
            self::Prosiding => 'Prosiding',
            self::Hki => 'HKI',
            self::Paten => 'Paten',
            self::Buku => 'Buku',
            self::TeknologiTepatGuna => 'Teknologi tepat guna',
            self::VideoKegiatan => 'Video kegiatan',
            self::PublikasiMedia => 'Publikasi media',
            self::TestimoniMitra => 'Testimoni mitra',
        };
    }

    /**
     * The kind of entry on the publication, HKI and patent pages this output
     * can be linked to, if any.
     *
     * @return class-string<Publikasi|Hki|Paten>|null
     */
    public function modelKarya(): ?string
    {
        return match ($this) {
            self::ArtikelJurnal, self::Prosiding, self::Buku => Publikasi::class,
            self::Hki => Hki::class,
            self::Paten => Paten::class,
            default => null,
        };
    }
}
