<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur GrowFast')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre compte a été créé avec succès.')
            ->line('Connectez-vous pour découvrir les opportunités de financement adaptées à votre startup.')
            ->action('Accéder à l\'application', url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Bienvenue sur GrowFast',
            'message' => 'Votre compte a été créé avec succès.',
        ];
    }
}
