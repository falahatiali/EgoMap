<?php

namespace App\Models;

use App\Enums\MoodEmotion;
use Database\Factories\MoodEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoodEntry extends Model
{
    /** @use HasFactory<MoodEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'emotion',
        'intensity',
        'ai_response',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emotion' => MoodEmotion::class,
            'intensity' => 'integer',
            'ai_response' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<UserIdea, $this>
     */
    public function ideas(): HasMany
    {
        return $this->hasMany(UserIdea::class);
    }
}
