<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_generated_programs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained(indexName: 'aprog_user_fk')->cascadeOnDelete();
            $table->foreignId('aether_user_profile_id')->constrained('aether_user_profiles', indexName: 'aprog_profile_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedSmallInteger('week_number')->default(1);
            $table->string('status', 16)->default('active');
            $table->string('applied_target', 16)->nullable();
            $table->foreignId('mission_enrollment_id')->nullable()->constrained('mission_enrollments', indexName: 'aprog_enrollment_fk')->nullOnDelete();
            $table->string('split', 32)->nullable();
            $table->text('shopping_list_summary')->nullable();
            $table->unsignedSmallInteger('metabolic_bmr')->nullable();
            $table->unsignedSmallInteger('metabolic_tdee')->nullable();
            $table->unsignedSmallInteger('metabolic_target_calories')->nullable();
            $table->unsignedSmallInteger('metabolic_protein_grams')->nullable();
            $table->unsignedSmallInteger('metabolic_fat_grams')->nullable();
            $table->unsignedSmallInteger('metabolic_carb_grams')->nullable();
            $table->decimal('metabolic_protein_g_per_kg', 4, 2)->nullable();
            $table->decimal('metabolic_activity_multiplier', 4, 2)->nullable();
            $table->string('coach_title')->nullable();
            $table->string('coach_week_focus')->nullable();
            $table->string('coach_mindset_focus')->nullable();
            $table->text('coach_habit_stack')->nullable();
            $table->text('coach_recovery_strategy')->nullable();
            $table->text('coach_supplement_advice')->nullable();
            $table->text('coach_disclaimer')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'applied_target']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_generated_programs');
    }
};
