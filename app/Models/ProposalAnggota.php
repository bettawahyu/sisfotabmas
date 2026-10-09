<?php

namespace App\Models;

use App\Enums\TipeAnggota;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipe', 'dosen_id', 'nama', 'nim', 'institusi', 'peran', 'uraian_tugas', 'token_undangan'])]
class ProposalAnggota extends Model
{
    protected $table = 'proposal_anggota';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => TipeAnggota::class,
            'dikonfirmasi_at' => 'datetime',
            'ditolak_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    /**
     * Only lecturer members confirm; students and external members are listed by the lead.
     */
    public function perluKonfirmasi(): bool
    {
        return $this->tipe === TipeAnggota::Dosen;
    }

    public function statusKonfirmasi(): string
    {
        return match (true) {
            ! $this->perluKonfirmasi() => 'Tidak perlu',
            $this->ditolak_at !== null => 'Menolak',
            $this->dikonfirmasi_at !== null => 'Bersedia',
            default => 'Menunggu',
        };
    }
}
