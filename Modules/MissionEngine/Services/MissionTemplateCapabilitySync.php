<?php

namespace Modules\MissionEngine\Services;

use Illuminate\Support\Collection;
use Modules\MissionEngine\Models\MissionCapabilityType;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Models\MissionTemplateCapability;

final class MissionTemplateCapabilitySync
{
    /**
     * @param  list<int>  $enabledCapabilityTypeIds
     */
    public function sync(MissionTemplate $template, array $enabledCapabilityTypeIds): void
    {
        $enabledIds = collect($enabledCapabilityTypeIds)->unique()->values();

        /** @var Collection<int, MissionCapabilityType> $types */
        $types = MissionCapabilityType::query()->orderBy('sort_order')->get();

        foreach ($types as $index => $type) {
            MissionTemplateCapability::query()->updateOrCreate(
                [
                    'template_id' => $template->id,
                    'capability_type_id' => $type->id,
                ],
                [
                    'is_enabled' => $enabledIds->contains($type->id),
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }

    public function ensureAllCapabilitiesExist(MissionTemplate $template): void
    {
        $this->sync($template, []);
    }
}
