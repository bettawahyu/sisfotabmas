<?php

namespace App\Support;

/**
 * Author mark shown in the page meta and the sidebar footer.
 */
class Tanda
{
    private const PENULIS = 'QGJldHRhLndhaHl1';

    private const KATA_KUNCI = 'sistem informasi penelitian, pengabdian kepada masyarakat, LPPM, hibah, dosen, publikasi, HKI';

    public static function penulis(): string
    {
        return base64_decode(self::PENULIS);
    }

    public static function kataKunci(): string
    {
        return self::KATA_KUNCI.', '.self::penulis();
    }
}
