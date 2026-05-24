<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Support\LocaleConfig;
use App\Support\TranslatableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslatableModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_returns_translation_for_current_locale(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create([
            'text' => [
                'en' => 'How do you feel?',
                'fa' => 'چه حسی داری؟',
            ],
        ]);

        app()->setLocale('en');
        $this->assertSame('How do you feel?', $question->text);

        app()->setLocale('fa');
        $this->assertSame('چه حسی داری؟', $question->fresh()->text);
    }

    public function test_question_falls_back_to_english_when_persian_missing(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create([
            'text' => [
                'en' => 'English only',
            ],
            'help_text' => null,
        ]);

        app()->setLocale('fa');

        $this->assertSame('English only', $question->fresh()->text);
    }

    public function test_translatable_json_builds_client_payload(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create([
            'text' => [
                'en' => 'Sample',
                'fa' => 'نمونه',
            ],
            'help_text' => null,
        ]);

        $payload = TranslatableJson::forModel($question, ['text']);

        $this->assertSame('Sample', $payload['en']["questions.{$question->id}.text"]);
        $this->assertSame('نمونه', $payload['fa']["questions.{$question->id}.text"]);
    }

    public function test_locale_config_lists_supported_locales(): void
    {
        $this->assertSame(['en', 'fa'], LocaleConfig::supported());
        $this->assertTrue(LocaleConfig::isRtl('fa'));
        $this->assertFalse(LocaleConfig::isRtl('en'));
    }
}
