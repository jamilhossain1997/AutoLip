<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutolibMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $subjectLine,
        private readonly string $htmlBody,
        private readonly ?string $textBody,
        private readonly ?string $fromName,
        private readonly ?string $fromAddress,
        private readonly array $replyToList,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->subjectLine);

        if ($this->fromAddress) {
            $envelope = new Envelope(
                from: new \Illuminate\Mail\Mailables\Address(
                    $this->fromAddress,
                    $this->fromName ?? config('mail.from.name'),
                ),
                subject: $this->subjectLine,
            );
        }

        if (! empty($this->replyTo)) {
            $replyToAddresses = array_map(
                fn($addr) => new \Illuminate\Mail\Mailables\Address($addr),
                $this->replyTo
            );

            $envelope = new Envelope(
                from: $this->fromAddress
                    ? new \Illuminate\Mail\Mailables\Address(
                        $this->fromAddress,
                        $this->fromName ?? config('mail.from.name'),
                    )
                    : null,
                replyTo: $replyToAddresses,
                subject: $this->subjectLine,
            );
        }

        return $envelope;
    }

    public function content(): Content
    {
        if ($this->textBody) {
            return new Content(
                htmlString: $this->htmlBody,
                textString: $this->textBody,
            );
        }

        return new Content(htmlString: $this->htmlBody);
    }

    public function attachments(): array
    {
        return [];
    }
}