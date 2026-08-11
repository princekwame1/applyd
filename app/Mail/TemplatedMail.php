<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $subjectLine  Already rendered (placeholders substituted).
     * @param  string  $bodyHtml  Already rendered and sanitised HTML.
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?string $heading = null,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template',
            with: [
                'heading' => $this->heading,
                'bodyHtml' => $this->bodyHtml,
                'ctaLabel' => $this->ctaLabel,
                'ctaUrl' => $this->ctaUrl,
            ],
        );
    }
}
