<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'template_id',
    'slug',
    'title',
    'description',
    'sort_order',
    'duration_days',
    'required_completion_count',
    'meta',
])]
class MissionTemplatePhase extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_days' => 'integer',
            'required_completion_count' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MissionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MissionTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<MissionEnrollment, $this>
     */
    public function enrollmentsOnPhase(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class, 'current_phase_id');
    }
}
