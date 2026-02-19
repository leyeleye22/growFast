<?php



namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScrapingFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $errorMessage,
        public ?string $sourceName = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GrowFast — Erreur de scraping',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scraping-failed',
        );
    }
}
