<?php



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
            ->subject("GrowFast — Don't forget: {$opp->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You had saved an opportunity. Don't forget to apply before the deadline!")
            ->line("**{$opp->title}**")
            ->line("Deadline: {$deadline}")
            ->when($opp->external_url, fn (MailMessage $m) => $m->action('View opportunity', $opp->external_url));
    }

    public function toArray(object $notifiable): array
    {
        $opp = $this->savedOpportunity->opportunity;

        return [
            'type' => 'opportunity_reminder',
            'title' => "Don't forget: {$opp->title}",
            'message' => "Deadline: " . ($opp->deadline?->format('d/m/Y') ?? '-'),
            'opportunity_id' => $opp->id,
            'external_url' => $opp->external_url,
        ];
    }
}
