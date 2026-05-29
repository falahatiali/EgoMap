<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MissionEngine\Enums\MissionFieldType;

#[Fillable([
    'template_id',
    'capability_type_id',
    'field_key',
    'label',
    'help_text',
    'field_type',
    'options',
    'validation_rules',
    'default_value',
    'is_required',
    'sort_order',
    'section',
    'conditional_logic',
])]
class MissionTemplateField extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['label', 'help_text'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_type' => MissionFieldType::class,
            'options' => 'array',
            'validation_rules' => 'array',
            'default_value' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'conditional_logic' => 'array',
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
