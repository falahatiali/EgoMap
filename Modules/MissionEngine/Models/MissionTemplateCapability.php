<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['template_id', 'capability_type_id', 'is_enabled', 'sort_order', 'label', 'config'])]
class MissionTemplateCapability extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['label'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'config' => 'array',
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
     * @return BelongsTo<MissionCapabilityType, $this>
     */
    public function capabilityType(): BelongsTo
    {
        return $this->belongsTo(MissionCapabilityType::class, 'capability_type_id');
    }
}
