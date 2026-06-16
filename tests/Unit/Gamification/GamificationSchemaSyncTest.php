<?php

namespace Tests\Unit\Gamification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\GamificationEngine\Support\GamificationSchemaSync;
use Tests\TestCase;

class GamificationSchemaSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_icon_column_to_existing_gamification_perks_table(): void
    {
        Schema::dropIfExists('gamification_perks');

        Schema::create('gamification_perks', function ($table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type', 20)->default('consumable');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->assertFalse(Schema::hasColumn('gamification_perks', 'icon'));

        GamificationSchemaSync::ensurePerksTable();

        $this->assertTrue(Schema::hasColumn('gamification_perks', 'icon'));
    }

    public function test_it_is_safe_when_icon_column_already_exists(): void
    {
        $this->artisan('migrate', [
            '--path' => 'Modules/GamificationEngine/database/migrations/2026_06_04_100400_create_gamification_perks_table.php',
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasColumn('gamification_perks', 'icon'));

        GamificationSchemaSync::ensurePerksTable();

        $this->assertTrue(Schema::hasColumn('gamification_perks', 'icon'));
    }
}
