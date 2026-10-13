<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Notifications\PengumumanTerbit;
use Illuminate\Support\Facades\Notification;

/**
 * Sends each announcement to its readers once, when it goes live.
 */
class SiaranPengumuman
{
    /**
     * Notify readers of every live announcement not yet sent. Returns how many were sent.
     */
    public function kirimYangTerbit(): int
    {
        $terbit = Pengumuman::whereNull('notifikasi_terkirim_at')
            ->where('terbit_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('berakhir_at')->orWhere('berakhir_at', '>', now()))
            ->get();

        foreach ($terbit as $pengumuman) {
            $this->kirim($pengumuman);
        }

        return $terbit->count();
    }

    public function kirim(Pengumuman $pengumuman): void
    {
        if ($pengumuman->notifikasi_terkirim_at !== null || ! $pengumuman->sedangTampil()) {
            return;
        }

        // Claim the row in one conditional update, so two overlapping runs can
        // never both send it: only the run that flips it from null goes on.
        $diklaim = Pengumuman::whereKey($pengumuman->id)
            ->whereNull('notifikasi_terkirim_at')
            ->update(['notifikasi_terkirim_at' => now()]);
        if ($diklaim === 0) {
            return;
        }
        $pengumuman->refresh();

        $pengumuman->penerima()->chunkById(200, function ($users) use ($pengumuman) {
            Notification::send($users, new PengumumanTerbit($pengumuman));
        });
    }
}
