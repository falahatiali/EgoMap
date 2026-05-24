<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Database\Factories\QuizSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class QuizSession extends Model
{
    /** @use HasFactory<QuizSessionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'quiz_id',
        'user_id',
        'guest_token',
        'locale',
        'status',
        'current_sort_order',
        'meta',
        'started_at',
        'completed_at',
    ];

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
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuizSession $session): void {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
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
