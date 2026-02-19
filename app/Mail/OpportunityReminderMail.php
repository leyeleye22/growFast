<?php



namespace App\Mail;

use App\Models\SavedOpportunity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpportunityReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public SavedOpportunity $savedOpportunity
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->savedOpportunity->opportunity->title;

        return new Envelope(
            subject: "GrowFast — N'oubliez pas : {$title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.opportunity-reminder',
        );
    }
}
