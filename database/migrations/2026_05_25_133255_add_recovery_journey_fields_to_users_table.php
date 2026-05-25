<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('recovery_phase', 20)->nullable()->after('password');
            $table->string('breakup_duration', 20)->nullable()->after('recovery_phase');
            $table->string('primary_struggle', 30)->nullable()->after('breakup_duration');
            $table->timestamp('recovery_triage_completed_at')->nullable()->after('primary_struggle');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'recovery_phase',
                'breakup_duration',
                'primary_struggle',
                'recovery_triage_completed_at',
            ]);
        });
    }
};
