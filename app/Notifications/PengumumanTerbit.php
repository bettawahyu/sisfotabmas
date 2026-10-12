<?php

namespace App\Notifications;

use App\Models\Pengumuman;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PengumumanTerbit extends Notification
{
    public function __construct(public Pengumuman $pengumuman) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return config('sisfotabmas.notifikasi_email') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengumuman: '.$this->pengumuman->judul)
            ->line(Str::limit($this->pengumuman->isi, 300))
            ->action('Baca pengumuman', route('pengumuman.show', $this->pengumuman));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Pengumuman baru: '.$this->pengumuman->judul,
            'url' => route('pengumuman.show', $this->pengumuman),
        ];
    }
}
