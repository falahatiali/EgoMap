<?php

namespace App\Models;

use App\Models\Concerns\HasAppTranslations;
use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasAppTranslations, HasFactory;

    /** @var list<string> */
    public array $translatable = ['label'];

    /** @var list<string> */
    protected $fillable = [
        'question_id',
        'sort_order',
        'label',
        'value',
        'scores',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'meta' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
