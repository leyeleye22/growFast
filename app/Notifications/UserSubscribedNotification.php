<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSubscribedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserSubscription $userSubscription
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sub = $this->userSubscription->subscription;
        $expiresAt = $this->userSubscription->expires_at->format('d/m/Y');

        return (new MailMessage)
            ->subject("GrowFast — Abonnement {$sub->name} activé")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre abonnement **{$sub->name}** a été activé.")
            ->line("Date d'expiration : {$expiresAt}");
    }

    public function toArray(object $notifiable): array
    {
        $sub = $this->userSubscription->subscription;

        return [
            'type' => 'subscription',
            'title' => "Abonnement {$sub->name} activé",
            'message' => "Expire le " . $this->userSubscription->expires_at->format('d/m/Y'),
            'subscription_id' => $sub->id,
        ];
    }
}
