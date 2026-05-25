<?php

namespace Tests\Feature\Pdf;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfMeta;
use App\DataTransferObjects\Pdf\PdfTheme;
use App\Enums\Pdf\PdfSectionType;
use App\Models\Quiz;
use App\Services\Pdf\Definitions\QuizResultPdfDefinitionFactory;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_it_generates_a_pdf_file_from_a_generic_document_definition(): void
    {
        $document = new PdfDocumentDefinition(
            locale: 'en',
            filename: 'sample-report.pdf',
            theme: new PdfTheme(
                accent: '#6366f1',
                accentSoft: '#eef2ff',
                accentDark: '#4f46e5',
            ),
            meta: new PdfMeta(
                title: 'Sample Report',
                brand: 'EgoMap',
                subtitle: 'A concise personality overview',
                footerNote: 'Generated for testing.',
            ),
            sections: [
                [
                    'type' => PdfSectionType::Hero->value,
                    'eyebrow' => 'Demo Test',
                    'badge' => 'INTJ',
                    'title' => 'The Architect',
                    'subtitle' => 'Strategic and independent thinker.',
                ],
                [
                    'type' => PdfSectionType::Paragraph->value,
                    'body' => 'This paragraph proves the generic PDF renderer works without quiz coupling.',
                ],
            ],
        );

        $path = app(PdfGeneratorService::class)->generate($document);

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path) ?: 0);
        $this->assertSame('%PDF', substr((string) file_get_contents($path), 0, 4));

        app(PdfGeneratorService::class)->delete($path);
        $this->assertFileDoesNotExist($path);
    }

    public function test_quiz_factory_builds_localized_document_without_touching_pdf_engine(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            app(QuizSessionService::class)->saveAnswer($session, $question, $question->options->first()->value);
        }

        app(QuizSessionService::class)->complete($session->fresh());
        $session->refresh()->load(['result.outcomeProfile', 'quiz']);

        $english = QuizResultPdfDefinitionFactory::fromSession($session, 'en');
        $persian = QuizResultPdfDefinitionFactory::fromSession($session, 'fa');

        $this->assertSame('en', $english->locale);
        $this->assertSame('fa', $persian->locale);
        $this->assertNotSame($english->meta->title, '');
        $this->assertGreaterThan(2, count($english->sections));
        $this->assertSame(PdfSectionType::Hero->value, $english->sections[0]['type']);
    }
}
