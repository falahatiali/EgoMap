<?php

namespace App\Jobs\Pdf;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfMailEnvelope;
use App\Mail\PdfDocumentMail;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class GenerateAndDeliverPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $mail
     * @param  class-string|null  $afterDeliveredCallback
     * @param  array<string, mixed>  $afterDeliveredPayload
     */
    public function __construct(
        public string $recipientEmail,
        public array $document,
        public array $mail,
        public ?string $afterDeliveredCallback = null,
        public array $afterDeliveredPayload = [],
    ) {}

    public function handle(PdfGeneratorService $pdfGenerator): void
    {
        $definition = PdfDocumentDefinition::fromArray($this->document);
        $envelope = PdfMailEnvelope::fromArray($this->mail);

        $path = $pdfGenerator->generate($definition);

        try {
            Mail::to($this->recipientEmail)->send(new PdfDocumentMail(
                mailEnvelope: $envelope,
                document: $definition,
                pdfPath: $path,
            ));
        } finally {
            $pdfGenerator->delete($path);
        }

        if ($this->afterDeliveredCallback !== null) {
            app($this->afterDeliveredCallback)->__invoke($this->afterDeliveredPayload);
        }
    }
}
