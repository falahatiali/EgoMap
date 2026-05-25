<?php

namespace App\Mail;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfMailEnvelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PdfDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PdfMailEnvelope $mailEnvelope,
        public PdfDocumentDefinition $document,
        public string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailEnvelope->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.pdf.document',
            with: [
                'mailEnvelope' => $this->mailEnvelope,
                'document' => $this->document,
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->document->filename)
                ->withMime('application/pdf'),
        ];
    }
}
