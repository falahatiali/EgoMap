<?php

namespace App\Services\Quiz;

use App\Models\QuizSession;
use App\Services\Pdf\Callbacks\MarkQuizSessionReportDelivered;
use App\Services\Pdf\Definitions\QuizResultPdfDefinitionFactory;
use App\Services\Pdf\PdfDeliveryService;
use App\Support\LocaleConfig;

class QuizResultDeliveryService
{
    public function __construct(
        private readonly QuizSessionService $quizSessionService,
        private readonly PdfDeliveryService $pdfDelivery,
    ) {}

    public function queueEmailReport(QuizSession $session, string $email): void
    {
        $this->quizSessionService->attachEmail($session, $email);

        $locale = LocaleConfig::resolve($session->locale ?? app()->getLocale());
        $session->loadMissing(['result.outcomeProfile', 'quiz']);

        $this->pdfDelivery->queueToEmail(
            recipientEmail: $email,
            document: QuizResultPdfDefinitionFactory::fromSession(
                $session->fresh(['result.outcomeProfile', 'quiz']),
                $locale,
            ),
            mail: QuizResultPdfDefinitionFactory::mailEnvelopeForSession($session, $locale),
            afterDeliveredCallback: MarkQuizSessionReportDelivered::class,
            afterDeliveredPayload: ['session_uuid' => $session->uuid],
        );
    }
}
