<?php

namespace App\Models;

use App\Enums\IdeaGoalCadence;
use App\Enums\IdeaStatus;
use Database\Factories\UserIdeaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdea extends Model
{
    /** @use HasFactory<UserIdeaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'mood_entry_id',
        'seed_text',
        'source',
        'status',
        'matured_details',
        'goal_cadence',
        'progress',
        'harvested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IdeaStatus::class,
            'goal_cadence' => IdeaGoalCadence::class,
            'matured_details' => 'array',
            'progress' => 'integer',
            'harvested_at' => 'datetime',
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
     * @return BelongsTo<MoodEntry, $this>
     */
    public function moodEntry(): BelongsTo
    {
        return $this->belongsTo(MoodEntry::class);
    }
}
