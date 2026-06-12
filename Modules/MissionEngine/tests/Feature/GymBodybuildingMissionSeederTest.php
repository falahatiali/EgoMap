<?php

namespace Modules\MissionEngine\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Models\MissionTemplate;
use Tests\TestCase;

class GymBodybuildingMissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gym_bodybuilding_template_has_expected_structure(): void
    {
        $this->seed(MissionEngineDatabaseSeeder::class);

        $template = MissionTemplate::query()
            ->where('slug', GymBodybuildingMissionSeeder::SLUG)
            ->with(['capabilities.capabilityType', 'fields', 'phases'])
            ->first();

        $this->assertNotNull($template);
        $this->assertTrue($template->isPublished());
        $this->assertSame('Gym & Bodybuilding', $template->getTranslation('title', 'en', true));
        $this->assertCount(4, $template->phases);
        $this->assertSame(2, $template->version);
        $this->assertSame('aether', $template->meta['engine_module'] ?? null);

        $enabledKeys = $template->capabilities
            ->where('is_enabled', true)
            ->map(fn ($c) => $c->capabilityType->key->value)
            ->all();

        $this->assertContains(MissionCapabilityKey::Schedule->value, $enabledKeys);
        $this->assertContains(MissionCapabilityKey::Measurement->value, $enabledKeys);
        $this->assertTrue($template->fields()->where('field_key', 'gym_days')->exists());
    }
}
