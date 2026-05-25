<?php

namespace App\Services\Pdf;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfMailEnvelope;
use App\Jobs\Pdf\GenerateAndDeliverPdfJob;

class PdfDeliveryService
{
    /**
     * Queue PDF generation and email delivery in the background.
     *
     * @param  class-string|null  $afterDeliveredCallback
     * @param  array<string, mixed>  $afterDeliveredPayload
     */
    public function queueToEmail(
        string $recipientEmail,
        PdfDocumentDefinition $document,
        PdfMailEnvelope $mail,
        ?string $afterDeliveredCallback = null,
        array $afterDeliveredPayload = [],
    ): void {
        GenerateAndDeliverPdfJob::dispatch(
            recipientEmail: $recipientEmail,
            document: $document->toArray(),
            mail: $mail->toArray(),
            afterDeliveredCallback: $afterDeliveredCallback,
            afterDeliveredPayload: $afterDeliveredPayload,
        );
    }
}
