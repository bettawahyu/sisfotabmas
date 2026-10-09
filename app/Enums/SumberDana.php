<?php

namespace App\Enums;

enum SumberDana: string
{
    case Internal = 'internal';
    case Bima = 'bima';
    case Industri = 'industri';
    case LuarNegeri = 'luar_negeri';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal institusi',
            self::Bima => 'BIMA (Kemdiktisaintek)',
            self::Industri => 'Industri',
            self::LuarNegeri => 'Luar negeri',
        };
    }
}
