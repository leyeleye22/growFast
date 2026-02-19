<?php



namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScrapingStartedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $triggeredBy = 'cron'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GrowFast — Scraping démarré',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scraping-started',
        );
    }
}
