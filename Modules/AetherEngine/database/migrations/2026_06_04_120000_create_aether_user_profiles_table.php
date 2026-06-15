<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_user_profiles')) {
            return;
        }

        Schema::create('aether_user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('age');
            $table->string('gender', 16);
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('body_fat_percent', 4, 1)->nullable();
            $table->string('training_experience', 32);
            $table->string('primary_goal', 32);
            $table->string('current_body_build', 32)->nullable();
            $table->string('target_body_goal', 32)->nullable();
            $table->string('gym_confidence', 32)->nullable();
            $table->string('secondary_goal', 32)->nullable();
            $table->decimal('target_weight_kg', 5, 2)->nullable();
            $table->decimal('target_body_fat_percent', 4, 1)->nullable();
            $table->unsignedTinyInteger('stress_level')->default(5);
            $table->decimal('sleep_hours', 3, 1)->default(7);
            $table->unsignedTinyInteger('training_days_per_week');
            $table->string('session_duration', 16);
            $table->string('preferred_workout_time', 16)->nullable();
            $table->string('equipment', 32);
            $table->json('injury_tags')->nullable();
            $table->text('injuries_limitations')->nullable();
            $table->string('dietary_pattern', 32);
            $table->json('allergies')->nullable();
            $table->string('cooking_ability', 32);
            $table->unsignedInteger('estimated_daily_calories')->nullable();
            $table->text('typical_meals')->nullable();
            $table->json('favorite_exercises')->nullable();
            $table->json('disliked_exercises')->nullable();
            $table->string('motivation_style', 32);
            $table->string('coaching_tone', 32);
            $table->json('supplements')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('questionnaire_completed_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_user_profiles');
    }
};
