<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bridges existing dev databases after gamification schema was extended
 * in the original create migrations (which had already run).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('no_contact_protocols') && ! Schema::hasColumn('no_contact_protocols', 'gamification_rewarded_at')) {
            Schema::table('no_contact_protocols', function (Blueprint $table): void {
                $table->timestamp('gamification_rewarded_at')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_shop_items');
        Schema::dropIfExists('gamification_perks');

        if (Schema::hasTable('no_contact_protocols') && Schema::hasColumn('no_contact_protocols', 'gamification_rewarded_at')) {
            Schema::table('no_contact_protocols', function (Blueprint $table): void {
                $table->dropColumn('gamification_rewarded_at');
            });
        }
    }
};
