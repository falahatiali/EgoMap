<?php

namespace Modules\GamificationEngine\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps gamification tables aligned during pre-release development
 * when migrations were already applied before schema changes.
 */
class GamificationSchemaSync
{
    public static function ensurePerksTable(): void
    {
        if (! Schema::hasTable('gamification_perks')) {
            return;
        }

        if (Schema::hasColumn('gamification_perks', 'icon')) {
            return;
        }

        Schema::table('gamification_perks', function (Blueprint $table): void {
            $table->string('icon', 40)->default('fa-gift')->after('description');
        });
    }
}
