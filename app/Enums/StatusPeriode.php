<?php

namespace App\Enums;

enum StatusPeriode: string
{
    case Draft = 'draft';
    case Dibuka = 'dibuka';
    case Ditutup = 'ditutup';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Dibuka => 'Dibuka',
            self::Ditutup => 'Ditutup',
            self::Selesai => 'Selesai',
        };
    }
}
