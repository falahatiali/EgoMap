<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('type')->default('likert')->after('slug');
            $table->json('scoring_config')->nullable()->after('settings');
            $table->unsignedSmallInteger('estimated_minutes')->nullable()->after('scoring_config');
            $table->unsignedSmallInteger('version')->default(1)->after('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['type', 'scoring_config', 'estimated_minutes', 'version']);
        });
    }
};
