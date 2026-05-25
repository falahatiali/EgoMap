<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizType;
use App\Models\OutcomeProfile;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizDimension;
use App\Support\MbtiContentCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MbtiQuizSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            ['slug' => 'mbti-personality'],
            [
                'type' => QuizType::Mbti,
                'name' => [
                    'en' => 'MBTI Personality Type',
                    'fa' => 'تیپ شخصیتی MBTI',
                ],
                'description' => [
                    'en' => '70-question Myers–Briggs style assessment across E/I, S/N, T/F, and J/P.',
                    'fa' => 'ارزیابی ۷۰ سؤالی به سبک مایرز-بریگز در ابعاد E/I، S/N، T/F و J/P.',
                ],
                'is_active' => true,
                'estimated_minutes' => 15,
                'version' => 2,
                'settings' => [
                    'ui' => 'immersive',
                    'sound_enabled' => true,
                    'keyboard_hints' => true,
                ],
                'scoring_config' => [
                    'engine' => 'mbti_axis',
                    'mode' => 'binary_choice',
                ],
            ],
        );

        $dimensions = collect([
            'ei' => ['en' => 'Extraversion / Introversion', 'fa' => 'برون‌گرایی / درون‌گرایی'],
            'sn' => ['en' => 'Sensing / Intuition', 'fa' => 'حسی / شهودی'],
            'tf' => ['en' => 'Thinking / Feeling', 'fa' => 'منطق / احساس'],
            'jp' => ['en' => 'Judging / Perceiving', 'fa' => 'قضاوت / ادراک'],
        ])->map(function (array $labels, string $key) use ($quiz) {
            return QuizDimension::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'key' => $key],
                [
                    'label' => ['en' => $labels['en'], 'fa' => $labels['fa']],
                    'sort_order' => match ($key) {
                        'ei' => 1,
                        'sn' => 2,
                        'tf' => 3,
                        default => 4,
                    },
                ],
            );
        });

        /** @var list<array{sort: int, dim: string, en: string, fa: string, options: list<array{key: string, letter: string, en: string, fa: string, icon: string, accent: string}>}> $bank */
        $bank = json_decode(File::get(database_path('data/mbti_questions.json')), true, 512, JSON_THROW_ON_ERROR);

        $activeSortOrders = [];

        foreach ($bank as $data) {
            $activeSortOrders[] = $data['sort'];

            $question = Question::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'sort_order' => $data['sort']],
                [
                    'type' => QuestionType::SingleChoice,
                    'quiz_dimension_id' => $dimensions[$data['dim']]->id,
                    'text' => ['en' => $data['en'], 'fa' => $data['fa']],
                    'help_text' => null,
                    'config' => ['format' => 'binary', 'required' => true],
                    'is_active' => true,
                ],
            );

            foreach ($data['options'] as $index => $option) {
                QuestionOption::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'value' => $option['key'],
                    ],
                    [
                        'sort_order' => $index + 1,
                        'label' => ['en' => $option['en'], 'fa' => $option['fa']],
                        'scores' => ['letter' => $option['letter']],
                        'meta' => [
                            'icon' => $option['icon'],
                            'accent' => $option['accent'],
                        ],
                    ],
                );
            }
        }

        Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotIn('sort_order', $activeSortOrders)
            ->update(['is_active' => false]);

        /** @var array<string, mixed> $characters */
        $characters = config('mbti_characters.characters', []);

        foreach (array_keys($characters) as $code) {
            $en = MbtiContentCatalog::profile($code, 'en') ?? [];
            $fa = MbtiContentCatalog::profile($code, 'fa') ?? [];

            OutcomeProfile::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'code' => $code],
                [
                    'title' => [
                        'en' => (string) ($en['archetype'] ?? strtoupper($code)),
                        'fa' => (string) ($fa['archetype'] ?? strtoupper($code)),
                    ],
                    'summary' => [
                        'en' => (string) ($en['tagline'] ?? ''),
                        'fa' => (string) ($fa['tagline'] ?? ''),
                    ],
                    'content' => MbtiContentCatalog::translatableOutcomeContent($code),
                    'match_rules' => null,
                    'sort_order' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
