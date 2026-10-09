<?php

namespace App\Enums;

enum TipeAnggota: string
{
    case Dosen = 'dosen';
    case Mahasiswa = 'mahasiswa';
    case Eksternal = 'eksternal';

    public function label(): string
    {
        return match ($this) {
            self::Dosen => 'Dosen',
            self::Mahasiswa => 'Mahasiswa',
            self::Eksternal => 'Pihak eksternal',
        };
    }
}
