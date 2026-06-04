<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MissionEngine\Enums\MissionDifficulty;
use Modules\MissionEngine\Enums\MissionTemplateStatus;

#[Fillable([
    'slug',
    'category_id',
    'parent_template_id',
    'title',
    'summary',
    'description',
    'icon',
    'difficulty',
    'estimated_days',
    'status',
    'version',
    'is_featured',
    'sort_order',
    'published_at',
    'created_by',
    'meta',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionTemplate extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'summary', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => MissionDifficulty::class,
            'status' => MissionTemplateStatus::class,
            'estimated_days' => 'integer',
            'version' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === MissionTemplateStatus::Published;
    }

    /**
     * @return BelongsTo<MissionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MissionCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<MissionTemplate, $this>
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<MissionTemplateCapability, $this>
     */
    public function capabilities(): HasMany
    {
        return $this->hasMany(MissionTemplateCapability::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<MissionTemplateField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(MissionTemplateField::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<MissionTemplatePhase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(MissionTemplatePhase::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<MissionEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class, 'template_id');
    }
}
