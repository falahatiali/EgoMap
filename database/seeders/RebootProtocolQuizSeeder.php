<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Support\RebootProtocolQuiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RebootProtocolQuizSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            ['slug' => RebootProtocolQuiz::SLUG],
            [
                'type' => QuizType::Custom,
                'name' => [
                    'en' => 'Reboot Protocol — Step 1',
                    'fa' => 'پروتکل ریبوت — قدم ۱',
                ],
                'description' => [
                    'en' => 'A 10-question stabilization check-in. No email required until your report.',
                    'fa' => 'یک چک‌این ۱۰ سؤالی برای تثبیت. تا گزارش، ایمیل لازم نیست.',
                ],
                'is_active' => true,
                'estimated_minutes' => 8,
                'version' => 1,
                'settings' => [
                    'ui' => 'immersive',
                    'sound_enabled' => false,
                    'show_welcome' => true,
                    'bilingual_options' => false,
                    'welcome' => [
                        'en' => 'You are about to take a 10-question diagnostic. No email needed. This will help us understand where you stand and give you a personalized first step.',
                        'fa' => 'قرار است یک تشخیص ۱۰ سؤالی بدهی. ایمیل لازم نیست. این به ما کمک می‌کند بدانیم کجایی و یک قدم اول شخصی‌سازی‌شده بگیری.',
                    ],
                ],
                'scoring_config' => [
                    'engine' => 'reboot_protocol',
                ],
            ],
        );

        /** @var list<array<string, mixed>> $bank */
        $bank = json_decode(
            File::get(database_path('data/reboot_protocol_questions.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $activeSortOrders = [];

        foreach ($bank as $data) {
            $sortOrder = (int) $data['sort_order'];
            $activeSortOrders[] = $sortOrder;

            $isMultiple = ($data['selection'] ?? 'single') === 'multiple';

            $question = Question::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'sort_order' => $sortOrder],
                [
                    'type' => $isMultiple ? QuestionType::MultipleChoice : QuestionType::SingleChoice,
                    'quiz_dimension_id' => null,
                    'text' => $data['text'],
                    'help_text' => $data['help_text'],
                    'config' => [
                        'required' => true,
                        'key' => $data['key'],
                        'selection' => $isMultiple ? 'multiple' : 'single',
                        'max_selections' => $isMultiple ? (int) ($data['max_selections'] ?? 3) : 1,
                    ],
                    'is_active' => true,
                ],
            );

            $optionSort = 0;

            foreach ($data['options'] as $option) {
                $optionSort++;

                QuestionOption::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'value' => (string) $option['value'],
                    ],
                    [
                        'sort_order' => $optionSort,
                        'label' => [
                            'en' => $option['en'],
                            'fa' => $option['fa'],
                        ],
                        'scores' => [],
                        'meta' => [
                            'accent' => match ($optionSort % 5) {
                                1 => 'emerald',
                                2 => 'blue',
                                3 => 'purple',
                                4 => 'amber',
                                default => 'rose',
                            },
                        ],
                    ],
                );
            }
        }

        Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotIn('sort_order', $activeSortOrders)
            ->update(['is_active' => false]);
    }
}
