<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes pre-module recovery gamification tables superseded by GamificationEngine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('recovery_activity_logs');
        Schema::dropIfExists('recovery_profiles');
    }

    public function down(): void
    {
        // Legacy tables are not recreated; use gamification_* tables instead.
    }
};
