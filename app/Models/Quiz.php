<?php

namespace App\Models;

use App\Enums\QuizType;
use App\Models\Concerns\HasAppTranslations;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'type',
        'name',
        'description',
        'is_active',
        'settings',
        'scoring_config',
        'estimated_minutes',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuizType::class,
            'is_active' => 'boolean',
            'settings' => 'array',
            'scoring_config' => 'array',
            'estimated_minutes' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return HasMany<QuizDimension, $this>
     */
    public function dimensions(): HasMany
    {
        return $this->hasMany(QuizDimension::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<OutcomeProfile, $this>
     */
    public function outcomeProfiles(): HasMany
    {
        return $this->hasMany(OutcomeProfile::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuizSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(QuizSession::class);
    }
}
