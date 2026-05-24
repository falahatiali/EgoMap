<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Models\Concerns\HasAppTranslations;
use App\Observers\AssignsUuidObserver;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id',
    'type',
    'quiz_dimension_id',
    'sort_order',
    'text',
    'help_text',
    'config',
    'is_active',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['text', 'help_text'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<QuizDimension, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(QuizDimension::class, 'quiz_dimension_id');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }
}
