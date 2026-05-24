<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizType;
use App\Models\OutcomeProfile;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizDimension;
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

        $types = [
            'intj' => ['en' => 'The Architect', 'fa' => 'معمار', 'summary_en' => 'Strategic, independent, and driven by long-range vision.', 'summary_fa' => 'استراتژیک، مستقل و هدایت‌شده با دید بلندمدت.'],
            'intp' => ['en' => 'The Logician', 'fa' => 'منطق‌دان', 'summary_en' => 'Analytical, curious, and energized by complex problems.', 'summary_fa' => 'تحلیل‌گر، کنجکاو و پرانرژی در مسائل پیچیده.'],
            'entj' => ['en' => 'The Commander', 'fa' => 'فرمانده', 'summary_en' => 'Decisive, ambitious, and natural at organizing people.', 'summary_fa' => 'قاطع، بلندپرواز و سازمان‌ده طبیعی.'],
            'entp' => ['en' => 'The Debater', 'fa' => 'مناظره‌گر', 'summary_en' => 'Inventive, quick-witted, and thrives on intellectual challenge.', 'summary_fa' => 'خلاق، زیرک و مشتاق چالش فکری.'],
            'infj' => ['en' => 'The Advocate', 'fa' => 'مدافع', 'summary_en' => 'Idealistic, empathetic, and guided by deep values.', 'summary_fa' => 'آرمانی، همدل و هدایت‌شده با ارزش‌های عمیق.'],
            'infp' => ['en' => 'The Mediator', 'fa' => 'میانجی', 'summary_en' => 'Creative, compassionate, and loyal to personal meaning.', 'summary_fa' => 'خلاق، دلسوز و وفادار به معنای شخصی.'],
            'enfj' => ['en' => 'The Protagonist', 'fa' => 'قهرمان', 'summary_en' => 'Charismatic, inspiring, and focused on helping others grow.', 'summary_fa' => 'کاریزماتیک، الهام‌بخش و متمرکز بر رشد دیگران.'],
            'enfp' => ['en' => 'The Campaigner', 'fa' => 'فعال', 'summary_en' => 'Enthusiastic, imaginative, and energized by connection.', 'summary_fa' => 'پرشور، خیال‌پرداز و انرژی‌گرفته از ارتباط.'],
            'istj' => ['en' => 'The Logistician', 'fa' => 'لجستیک', 'summary_en' => 'Reliable, practical, and committed to duty.', 'summary_fa' => 'قابل اعتماد، عملی و متعهد به وظیفه.'],
            'isfj' => ['en' => 'The Defender', 'fa' => 'مدافع', 'summary_en' => 'Warm, responsible, and devoted to protecting others.', 'summary_fa' => 'گرم، مسئولیت‌پذیر و فداکار در حمایت.'],
            'estj' => ['en' => 'The Executive', 'fa' => 'مدیر', 'summary_en' => 'Organized, direct, and focused on order and results.', 'summary_fa' => 'منظم، مستقیم و متمرکز بر نظم و نتیجه.'],
            'esfj' => ['en' => 'The Consul', 'fa' => 'کنسول', 'summary_en' => 'Supportive, sociable, and attentive to harmony.', 'summary_fa' => 'حمایت‌گر، اجتماعی و هوشیار نسبت به هماهنگی.'],
            'istp' => ['en' => 'The Virtuoso', 'fa' => 'استاد', 'summary_en' => 'Bold, practical, and skilled at hands-on problem solving.', 'summary_fa' => 'جسور، عملی و ماهر در حل مسئله عملی.'],
            'isfp' => ['en' => 'The Adventurer', 'fa' => 'ماجراجو', 'summary_en' => 'Artistic, flexible, and lives by personal values.', 'summary_fa' => 'هنری، انعطاف‌پذیر و زندگی بر اساس ارزش‌ها.'],
            'estp' => ['en' => 'The Entrepreneur', 'fa' => 'کارآفرین', 'summary_en' => 'Energetic, perceptive, and thrives in the moment.', 'summary_fa' => 'پرانرژی، هوشیار و شکوفا در لحظه.'],
            'esfp' => ['en' => 'The Entertainer', 'fa' => 'سرگرم‌کننده', 'summary_en' => 'Spontaneous, fun-loving, and lights up social spaces.', 'summary_fa' => 'آنی، سرگرم‌کننده و روشن‌کننده فضای اجتماعی.'],
        ];

        foreach ($types as $code => $meta) {
            /** @var array<string, array<string, mixed>> $profileContent */
            $profileContent = require database_path('data/mbti_profile_content.php');
            $content = $profileContent[$code] ?? null;

            OutcomeProfile::query()->updateOrCreate(
                ['quiz_id' => $quiz->id, 'code' => $code],
                [
                    'title' => ['en' => $meta['en'], 'fa' => $meta['fa']],
                    'summary' => ['en' => $meta['summary_en'], 'fa' => $meta['summary_fa']],
                    'content' => $content,
                    'match_rules' => null,
                    'sort_order' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
