<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizDimension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $textEn = fake()->sentence().'?';

        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::Likert,
            'quiz_dimension_id' => null,
            'sort_order' => fake()->numberBetween(1, 50),
            'text' => [
                'en' => $textEn,
                'fa' => 'سؤال: '.$textEn,
            ],
            'help_text' => [
                'en' => 'Answer honestly — there are no wrong responses.',
                'fa' => 'صادقانه پاسخ بده — پاسخ غلطی وجود ندارد.',
            ],
            'config' => [
                'required' => true,
                'reverse_scored' => false,
            ],
            'is_active' => true,
        ];
    }

    public function forDimension(QuizDimension $dimension): static
    {
        return $this->state(fn () => [
            'quiz_id' => $dimension->quiz_id,
            'quiz_dimension_id' => $dimension->id,
        ]);
    }
}
