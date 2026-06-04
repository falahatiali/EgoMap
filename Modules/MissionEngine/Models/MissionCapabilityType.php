<?php

namespace Modules\MissionEngine\Models;

use App\Models\Concerns\HasAppTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MissionEngine\Enums\MissionCapabilityKey;

#[Fillable(['key', 'name', 'description', 'icon', 'is_core', 'sort_order', 'default_config'])]
class MissionCapabilityType extends Model
{
    use HasAppTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => MissionCapabilityKey::class,
            'is_core' => 'boolean',
            'sort_order' => 'integer',
            'default_config' => 'array',
        ];
    }

    /**
     * @return HasMany<MissionTemplateCapability, $this>
     */
    public function templateCapabilities(): HasMany
    {
        return $this->hasMany(MissionTemplateCapability::class, 'capability_type_id');
    }
}
