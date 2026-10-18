<?php

namespace App\Support;

/**
 * Author mark shown in the page meta and the sidebar footer.
 */
class Tanda
{
    private const PENULIS = 'QGJldHRhLndhaHl1';

    private const TAUTAN = 'aHR0cHM6Ly9naXRodWIuY29tL2JldHRhd2FoeXU=';

    private const KATA_KUNCI = 'sistem informasi penelitian, pengabdian kepada masyarakat, LPPM, hibah, dosen, publikasi, HKI';

    public static function penulis(): string
    {
        return base64_decode(self::PENULIS);
    }

    public static function tautan(): string
    {
        return base64_decode(self::TAUTAN);
    }

    public static function kataKunci(): string
    {
        return self::KATA_KUNCI.', '.self::penulis();
    }
}
