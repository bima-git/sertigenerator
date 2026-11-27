<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Email reset kata sandi berbahasa Indonesia; tautan menuju halaman /reset-sandi/{token}. */
class ResetKataSandi extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $menit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset kata sandi Sistem Sertifikat XCODE')
            ->greeting("Halo, {$notifiable->nama}")
            ->line('Kami menerima permintaan untuk membuat kata sandi baru bagi akunmu.')
            ->action('Buat Kata Sandi Baru', $this->resetUrl($notifiable))
            ->line("Tautan ini berlaku {$menit} menit dan hanya bisa dipakai sekali.")
            ->line('Bila kamu tidak meminta reset, abaikan email ini. Kata sandimu tetap sama.')
            ->salutation('Salam, Tim XCODE Training');
    }
}
