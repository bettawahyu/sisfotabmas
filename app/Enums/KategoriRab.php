<?php

namespace App\Enums;

enum KategoriRab: string
{
    case Bahan = 'bahan';
    case PengumpulanData = 'pengumpulan_data';
    case AnalisisData = 'analisis_data';
    case SewaPeralatan = 'sewa_peralatan';
    case PelaporanPublikasi = 'pelaporan_publikasi';
    case Honorarium = 'honorarium';

    public function label(): string
    {
        return match ($this) {
            self::Bahan => 'Biaya belanja bahan',
            self::PengumpulanData => 'Biaya pengumpulan data',
            self::AnalisisData => 'Biaya analisis data',
            self::SewaPeralatan => 'Biaya sewa peralatan',
            self::PelaporanPublikasi => 'Biaya pelaporan dan publikasi',
            self::Honorarium => 'Biaya honorarium',
        };
    }
}
