<?php

namespace App\Enums;

enum StatusProposal: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case Ditinjau = 'ditinjau';
    case Dikembalikan = 'dikembalikan';
    case TidakDidanai = 'tidak_didanai';
    case Disetujui = 'disetujui';
    case Didanai = 'didanai';
    case Berjalan = 'berjalan';
    case Dimonev = 'dimonev';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Diajukan => 'Diajukan',
            self::Ditinjau => 'Direview',
            self::Dikembalikan => 'Dikembalikan',
            self::TidakDidanai => 'Tidak didanai',
            self::Disetujui => 'Disetujui',
            self::Didanai => 'Didanai',
            self::Berjalan => 'Berjalan',
            self::Dimonev => 'Dimonev',
            self::Selesai => 'Selesai',
        };
    }

    /**
     * The statuses a proposal may move to from this one. A proposal only moves
     * forward, except that a returned proposal goes back to the lead for revision.
     *
     * @return list<self>
     */
    public function bolehMenjadi(): array
    {
        return match ($this) {
            self::Draft => [self::Diajukan],
            self::Diajukan => [self::Ditinjau, self::Dikembalikan, self::TidakDidanai],
            self::Dikembalikan => [self::Diajukan, self::TidakDidanai],
            self::Ditinjau => [self::Disetujui, self::TidakDidanai],
            self::Disetujui => [self::Didanai],
            self::Didanai => [self::Berjalan],
            self::Berjalan => [self::Dimonev],
            self::Dimonev => [self::Selesai],
            self::TidakDidanai, self::Selesai => [],
        };
    }

    public function bolehMenjadiStatus(self $tujuan): bool
    {
        return in_array($tujuan, $this->bolehMenjadi(), true);
    }

    /**
     * Whether the lead can still change the proposal's content.
     */
    public function bisaDiubahPengusul(): bool
    {
        return in_array($this, [self::Draft, self::Dikembalikan], true);
    }
}
