<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Facades\Notification;

/**
 * Decides who hears about what and sends them a Pemberitahuan.
 */
class Pemberitahu
{
    public function keUser(?User $user, string $judul, string $pesan, ?string $url = null): void
    {
        $user?->notify(new Pemberitahuan($judul, $pesan, $url));
    }

    /**
     * Everyone holding any of the given roles.
     */
    public function kePeran(array $peran, string $judul, string $pesan, ?string $url = null): void
    {
        $penerima = User::whereHas('roles', fn ($q) => $q->whereIn('kode', $peran))->get();
        Notification::send($penerima, new Pemberitahuan($judul, $pesan, $url));
    }

    /**
     * The proposal lead, linked to the proposal page.
     */
    public function keKetua(Proposal $proposal, string $judul, string $pesan): void
    {
        $this->keUser($proposal->ketua?->user, $judul, $pesan, route('proposal.show', $proposal));
    }
}
