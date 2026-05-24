<?php

namespace App\Models;

use App\Models\Concerns\HasAppTranslations;
use App\Observers\AssignsUuidObserver;
use Database\Factories\OutcomeProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id',
    'code',
    'title',
    'summary',
    'content',
    'match_rules',
    'sort_order',
    'is_active',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class OutcomeProfile extends Model
{
    /** @use HasFactory<OutcomeProfileFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['title', 'summary', 'content'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_rules' => 'array',
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
     * @return HasMany<QuizResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }
}
