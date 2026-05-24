<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizType;
use App\Models\OutcomeProfile;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizDimension;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::query()->updateOrCreate(
            ['slug' => 'relationship-patterns'],
            [
                'type' => QuizType::Likert,
                'name' => [
                    'en' => 'Relationship Patterns',
                    'fa' => 'الگوهای رابطه',
                ],
                'description' => [
                    'en' => 'Discover your attachment style and relationship triggers.',
                    'fa' => 'سبک دلبستگی و محرک‌های رابطه‌ات را بشناس.',
                ],
                'is_active' => true,
                'estimated_minutes' => 5,
                'version' => 1,
                'settings' => [
                    'likert_min' => 1,
                    'likert_max' => 5,
                    'likert_labels' => [
                        'en' => ['Strongly disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly agree'],
                        'fa' => ['کاملاً مخالفم', 'مخالفم', 'بی‌طرف', 'موافقم', 'کاملاً موافقم'],
                    ],
                ],
                'scoring_config' => [
                    'engine' => 'weighted_sum',
                ],
            ],
        );

        $anxiety = QuizDimension::query()->updateOrCreate(
            ['quiz_id' => $quiz->id, 'key' => 'anxiety'],
            [
                'label' => ['en' => 'Attachment Anxiety', 'fa' => 'اضطراب دلبستگی'],
                'description' => ['en' => 'Fear of abandonment and need for reassurance.', 'fa' => 'ترس از رها شدن و نیاز به اطمینان.'],
                'sort_order' => 1,
            ],
        );

        $attunement = QuizDimension::query()->updateOrCreate(
            ['quiz_id' => $quiz->id, 'key' => 'attunement'],
            [
                'label' => ['en' => 'Emotional Attunement', 'fa' => 'هم‌آهنگی احساسی'],
                'sort_order' => 2,
            ],
        );

        $questions = [
            [
                'sort_order' => 1,
                'dimension' => $anxiety,
                'text' => [
                    'en' => 'After an argument, how much do you feel the need to pull away from your partner?',
                    'fa' => 'بعد از یک جر و بحث، چقدر احساس می‌کنی باید از طرف مقابل فاصله بگیری؟',
                ],
                'help_text' => [
                    'en' => '1 = not at all, 5 = I urgently need space',
                    'fa' => '۱ = اصلاً، ۵ = فوراً به تنهایی نیاز دارم',
                ],
            ],
            [
                'sort_order' => 2,
                'dimension' => $anxiety,
                'text' => [
                    'en' => 'When your partner is distant, how often do you seek reassurance?',
                    'fa' => 'وقتی طرف مقابل دور است، چقدر به دنبال اطمینان می‌گردی؟',
                ],
                'help_text' => [
                    'en' => 'Think about your most recent relationship.',
                    'fa' => 'به تازه‌ترین رابطه‌ات فکر کن.',
                ],
            ],
            [
                'sort_order' => 3,
                'dimension' => $attunement,
                'text' => [
                    'en' => 'I often sense what my partner feels before they tell me.',
                    'fa' => 'اغلب قبل از اینکه بگویند، حال طرف مقابل را می‌فهمم.',
                ],
                'help_text' => null,
            ],
        ];

        foreach ($questions as $data) {
            Question::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'sort_order' => $data['sort_order']],
                [
                    'type' => QuestionType::Likert,
                    'quiz_dimension_id' => $data['dimension']->id,
                    'text' => $data['text'],
                    'help_text' => $data['help_text'],
                    'config' => ['required' => true, 'reverse_scored' => false],
                    'is_active' => true,
                ],
            );
        }

        OutcomeProfile::query()->updateOrCreate(
            ['quiz_id' => $quiz->id, 'code' => 'anxious_attuned'],
            [
                'title' => ['en' => 'Anxious-Attuned', 'fa' => 'اضطرابی-آگاه'],
                'summary' => [
                    'en' => 'Deeply attuned with a strong need for closeness and reassurance.',
                    'fa' => 'هم‌آهنگی عمیق با نیاز قوی به نزدیکی و اطمینان.',
                ],
                'match_rules' => ['anxiety' => ['min' => 3.5]],
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }
}
