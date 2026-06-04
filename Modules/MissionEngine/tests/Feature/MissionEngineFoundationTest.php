<?php

namespace Modules\MissionEngine\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionCapabilityType;
use Modules\MissionEngine\Models\MissionCategory;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionEnrollmentService;
use Modules\MissionEngine\Services\MissionTemplateCapabilitySync;
use Tests\TestCase;

class MissionEngineFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    public function test_capability_and_category_catalog_is_seeded(): void
    {
        $this->assertGreaterThanOrEqual(10, MissionCapabilityType::query()->count());
        $this->assertGreaterThanOrEqual(4, MissionCategory::query()->count());
        $this->assertTrue(
            MissionCapabilityType::query()->where('key', MissionCapabilityKey::Schedule)->exists(),
        );
    }

    public function test_admin_can_create_template_with_capabilities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $category = MissionCategory::query()->where('slug', 'body')->firstOrFail();

        $template = new MissionTemplate([
            'slug' => 'gym-club',
            'category_id' => $category->id,
            'difficulty' => 'beginner',
            'estimated_days' => 90,
            'status' => MissionTemplateStatus::Draft,
            'version' => 1,
            'created_by' => $admin->id,
        ]);
        $template->setTranslation('title', 'en', 'Gym Club');
        $template->save();

        $schedule = MissionCapabilityType::query()->where('key', MissionCapabilityKey::Schedule)->firstOrFail();
        $nutrition = MissionCapabilityType::query()->where('key', MissionCapabilityKey::Nutrition)->firstOrFail();

        app(MissionTemplateCapabilitySync::class)->sync($template, [$schedule->id, $nutrition->id]);

        $template->refresh();

        $finance = MissionCapabilityType::query()->where('key', MissionCapabilityKey::Finance)->firstOrFail();

        $this->assertCount(11, $template->capabilities);
        $this->assertTrue($template->capabilities->firstWhere('capability_type_id', $schedule->id)?->is_enabled);
        $this->assertFalse($template->capabilities->firstWhere('capability_type_id', $finance->id)?->is_enabled);
    }

    public function test_user_can_enroll_in_published_template_with_snapshot(): void
    {
        $user = User::factory()->create();

        $template = new MissionTemplate([
            'slug' => 'shooting-range',
            'status' => MissionTemplateStatus::Published,
            'published_at' => now(),
            'version' => 1,
        ]);
        $template->setTranslation('title', 'en', 'Shooting Range');
        $template->save();

        $schedule = MissionCapabilityType::query()->where('key', MissionCapabilityKey::Schedule)->firstOrFail();
        app(MissionTemplateCapabilitySync::class)->sync($template, [$schedule->id]);

        $enrollment = app(MissionEnrollmentService::class)->enroll($user, $template->fresh());

        $this->assertSame(MissionEnrollmentStatus::Active, $enrollment->status);
        $this->assertSame('shooting-range', $enrollment->template_snapshot['slug']);
        $this->assertSame('Shooting Range', $enrollment->template_snapshot['title']['en']);
        $this->assertCount(1, $enrollment->template_snapshot['capabilities']);
    }

    public function test_draft_template_cannot_be_enrolled(): void
    {
        $user = User::factory()->create();

        $template = new MissionTemplate([
            'slug' => 'draft-only',
            'status' => MissionTemplateStatus::Draft,
            'version' => 1,
        ]);
        $template->setTranslation('title', 'en', 'Draft');
        $template->save();

        $this->expectException(\InvalidArgumentException::class);

        app(MissionEnrollmentService::class)->enroll($user, $template);
    }
}
