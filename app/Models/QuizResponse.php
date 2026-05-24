<?php

namespace App\Models;

use Database\Factories\QuizResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResponse extends Model
{
    /** @use HasFactory<QuizResponseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'quiz_session_id',
        'question_id',
        'value',
        'answered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'answered_at' => 'datetime',
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
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
