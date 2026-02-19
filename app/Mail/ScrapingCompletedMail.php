<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScrapingCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $entriesFound,
        public int $jobsDispatched,
        public string $triggeredBy = 'cron'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GrowFast — Scraping terminé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scraping-completed',
        );
    }
}
