<?php

namespace App\Enums;

enum JenisKegiatan: string
{
    case Penelitian = 'penelitian';
    case Pkm = 'pkm';

    public function label(): string
    {
        return match ($this) {
            self::Penelitian => 'Penelitian',
            self::Pkm => 'Pengabdian kepada Masyarakat',
        };
    }
}
