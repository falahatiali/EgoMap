<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Observers\AssignsUuidObserver;
use Database\Factories\QuizSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'quiz_id',
    'user_id',
    'guest_token',
    'locale',
    'status',
    'current_sort_order',
    'meta',
    'started_at',
    'completed_at',
    'email',
    'email_report_sent_at',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class QuizSession extends Model
{
    /** @use HasFactory<QuizSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'meta' => 'array',
            'current_sort_order' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'email_report_sent_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<QuizResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(QuizResponse::class);
    }

    /**
     * @return HasOne<QuizResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(QuizResult::class);
    }
}
