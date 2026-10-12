<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification type for the whole app: a title, a short message and the
 * page to open. It always shows in the app, and is also emailed once
 * NOTIFIKASI_EMAIL is on and the campus SMTP server is set in .env.
 */
class Pemberitahuan extends Notification
{
    public function __construct(
        public string $judul,
        public string $pesan,
        public ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return config('sisfotabmas.notifikasi_email') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pesan = (new MailMessage)->subject($this->judul)->greeting('Yth. '.$notifiable->name)->line($this->pesan);

        return $this->url ? $pesan->action('Buka di '.config('app.name'), $this->url) : $pesan;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'url' => $this->url,
        ];
    }
}
