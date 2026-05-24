<?php

namespace App\Models;

use App\Models\Concerns\HasAppTranslations;
use Database\Factories\QuizDimensionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizDimension extends Model
{
    /** @use HasFactory<QuizDimensionFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['label', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'quiz_id',
        'key',
        'label',
        'description',
        'sort_order',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
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
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
