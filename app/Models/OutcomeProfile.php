<?php

namespace App\Models;

use App\Models\Concerns\HasAppTranslations;
use Database\Factories\OutcomeProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutcomeProfile extends Model
{
    /** @use HasFactory<OutcomeProfileFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['title', 'summary'];

    /** @var list<string> */
    protected $fillable = [
        'quiz_id',
        'code',
        'title',
        'summary',
        'match_rules',
        'sort_order',
        'is_active',
    ];

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
