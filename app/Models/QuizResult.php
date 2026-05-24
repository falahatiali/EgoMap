<?php

namespace App\Models;

use App\Enums\ResultStatus;
use App\Observers\AssignsUuidObserver;
use Database\Factories\QuizResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_session_id',
    'outcome_profile_id',
    'dimension_scores',
    'free_report',
    'premium_report',
    'status',
    'ai_model',
    'ai_prompt_version',
    'generated_at',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class QuizResult extends Model
{
    /** @use HasFactory<QuizResultFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimension_scores' => 'array',
            'free_report' => 'array',
            'premium_report' => 'array',
            'status' => ResultStatus::class,
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuizSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class, 'quiz_session_id');
    }

    /**
     * @return BelongsTo<OutcomeProfile, $this>
     */
    public function outcomeProfile(): BelongsTo
    {
        return $this->belongsTo(OutcomeProfile::class);
    }
}
