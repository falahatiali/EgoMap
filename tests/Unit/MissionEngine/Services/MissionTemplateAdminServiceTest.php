<?php

namespace Tests\Unit\MissionEngine\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Enums\MissionFieldType;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionTemplateAdminService;
use Tests\TestCase;

class MissionTemplateAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    public function test_duplicate_copies_fields_phases_and_capabilities(): void
    {
        $template = MissionTemplate::query()->where('slug', 'gym-bodybuilding')->firstOrFail();

        $copy = app(MissionTemplateAdminService::class)->duplicate($template);

        $this->assertNotSame($template->id, $copy->id);
        $this->assertStringStartsWith('gym-bodybuilding-copy', $copy->slug);
        $this->assertSame(MissionTemplateStatus::Draft, $copy->status);
        $this->assertSame($template->fields()->count(), $copy->fields()->count());
        $this->assertSame($template->phases()->count(), $copy->phases()->count());
        $this->assertSame(
            $template->capabilities()->where('is_enabled', true)->count(),
            $copy->capabilities()->where('is_enabled', true)->count(),
        );
    }

    public function test_publish_readiness_warns_when_template_is_incomplete(): void
    {
        $template = MissionTemplate::query()->make([
            'slug' => 'empty-mission',
            'status' => MissionTemplateStatus::Draft,
            'difficulty' => 'beginner',
            'version' => 1,
        ]);
        $template->setTranslation('title', 'en', '');
        $template->save();

        $readiness = app(MissionTemplateAdminService::class)->publishReadiness($template);

        $this->assertFalse($readiness['ok']);
        $this->assertNotEmpty($readiness['warnings']);
    }

    public function test_create_phase_persists_title_before_insert(): void
    {
        $template = MissionTemplate::query()->where('slug', 'gym-bodybuilding')->firstOrFail();

        $phase = app(MissionTemplateAdminService::class)->createPhase($template, [
            'slug' => 'test-phase',
            'title_en' => 'Test phase',
            'title_fa' => 'فاز تست',
            'duration_days' => 7,
        ]);

        $this->assertSame('test_phase', $phase->slug);
        $this->assertSame('Test phase', $phase->getTranslation('title', 'en', true));
        $this->assertDatabaseHas('mission_template_phases', [
            'id' => $phase->id,
            'slug' => 'test_phase',
        ]);
    }

    public function test_create_field_persists_bilingual_labels(): void
    {
        $template = MissionTemplate::query()->where('slug', 'gym-bodybuilding')->firstOrFail();

        $field = app(MissionTemplateAdminService::class)->createField($template, [
            'field_key' => 'custom_note',
            'field_type' => MissionFieldType::Textarea->value,
            'section' => 'daily',
            'label_en' => 'Daily note',
            'label_fa' => 'یادداشت روز',
            'help_en' => 'Optional reflection',
            'default_value_json' => '"hello"',
        ]);

        $this->assertSame('custom_note', $field->field_key);
        $this->assertSame('Daily note', $field->getTranslation('label', 'en', true));
        $this->assertSame('یادداشت روز', $field->getTranslation('label', 'fa', true));
        $this->assertSame('hello', $field->default_value);
    }
}
