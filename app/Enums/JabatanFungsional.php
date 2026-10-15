<?php

namespace App\Enums;

/**
 * The national lecturer ranks, lowest first, so a scheme can ask for a minimum rank.
 */
enum JabatanFungsional: string
{
    case TenagaPengajar = 'tenaga_pengajar';
    case AsistenAhli = 'asisten_ahli';
    case Lektor = 'lektor';
    case LektorKepala = 'lektor_kepala';
    case GuruBesar = 'guru_besar';

    public function label(): string
    {
        return match ($this) {
            self::TenagaPengajar => 'Tenaga Pengajar',
            self::AsistenAhli => 'Asisten Ahli',
            self::Lektor => 'Lektor',
            self::LektorKepala => 'Lektor Kepala',
            self::GuruBesar => 'Guru Besar',
        };
    }

    /**
     * Position in the ladder, 1 for Tenaga Pengajar up to 5 for Guru Besar.
     */
    public function jenjang(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    public function setidaknya(self $minimal): bool
    {
        return $this->jenjang() >= $minimal->jenjang();
    }

    /**
     * Reads a rank typed as free text before the dropdown existed, such as
     * "lektor kepala", "LK" or "Profesor". Returns null when it cannot tell.
     */
    public static function dariTeks(?string $teks): ?self
    {
        $teks = strtolower(trim(preg_replace('/[^a-z ]+/i', ' ', (string) $teks)));
        $teks = preg_replace('/\s+/', ' ', $teks);

        return match (true) {
            $teks === '' => null,
            in_array($teks, ['gb', 'guru besar', 'profesor', 'professor', 'prof'], true) => self::GuruBesar,
            in_array($teks, ['lk', 'lektor kepala'], true) => self::LektorKepala,
            in_array($teks, ['l', 'lektor'], true) => self::Lektor,
            in_array($teks, ['aa', 'asisten ahli', 'asisten'], true) => self::AsistenAhli,
            in_array($teks, ['tp', 'tenaga pengajar', 'pengajar', 'belum ada', 'non jad', 'nonjad'], true) => self::TenagaPengajar,
            default => self::tryFrom(str_replace(' ', '_', $teks)),
        };
    }
}
