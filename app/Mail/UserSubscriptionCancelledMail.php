<?php



namespace App\Mail;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserSubscriptionCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserSubscription $userSubscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GrowFast — Abonnement annulé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-subscription-cancelled',
        );
    }
}
