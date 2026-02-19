<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SavedOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpportunityReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SavedOpportunity $savedOpportunity
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $opp = $this->savedOpportunity->opportunity;
        $deadline = $opp->deadline?->format('d/m/Y') ?? '-';

        return (new MailMessage)
            ->subject("GrowFast — N'oubliez pas : {$opp->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous aviez sauvegardé une opportunité. N'oubliez pas de candidater avant la date limite !")
            ->line("**{$opp->title}**")
            ->line("Date limite : {$deadline}")
            ->when($opp->external_url, fn (MailMessage $m) => $m->action('Voir l\'opportunité', $opp->external_url));
    }

    public function toArray(object $notifiable): array
    {
        $opp = $this->savedOpportunity->opportunity;

        return [
            'type' => 'opportunity_reminder',
            'title' => "N'oubliez pas : {$opp->title}",
            'message' => "Date limite : " . ($opp->deadline?->format('d/m/Y') ?? '-'),
            'opportunity_id' => $opp->id,
            'external_url' => $opp->external_url,
        ];
    }
}
