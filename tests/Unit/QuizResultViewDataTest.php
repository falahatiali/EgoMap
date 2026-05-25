<?php

namespace Tests\Unit;

use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionService;
use App\Support\QuizResultViewData;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizResultViewDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    #[Test]
    public function it_merges_mbti_catalog_content_for_completed_session(): void
    {
        $session = $this->completeSession();
        $typeCode = strtolower((string) $session->result->free_report['type_code']);

        $data = QuizResultViewData::fromSession($session);

        $this->assertNotEmpty($data['content']['narrative'] ?? '');
        $this->assertNotEmpty($data['content']['communication_style'] ?? '');
        $this->assertNotEmpty($data['content']['featured_people'] ?? []);
        $this->assertSame('sentinel', $data['palette']['group']);
        $this->assertSame('sentinel', $data['content']['group']);
        $this->assertSame($typeCode, strtolower((string) ($data['report']['type_code'] ?? '')));
        $this->assertNotEmpty($data['report']['dimensions'][0]['axis_name'] ?? '');
    }

    #[Test]
    public function it_exposes_famous_examples_from_featured_people(): void
    {
        $session = $this->completeSession();

        $data = QuizResultViewData::fromSession($session);

        $this->assertNotEmpty($data['content']['famous_examples']);
        $this->assertSame(
            $data['content']['featured_people'][0]['name'],
            $data['content']['famous_examples'][0],
        );
    }

    private function completeSession(): QuizSession
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());

        return $session->fresh(['result.outcomeProfile', 'quiz']);
    }
}
