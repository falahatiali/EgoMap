<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mission_capability_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_core')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('default_config')->nullable();
            $table->timestamps();
        });

        Schema::create('mission_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('mission_categories')->nullOnDelete();
            $table->foreignId('parent_template_id')->nullable()->constrained('mission_templates')->nullOnDelete();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('difficulty')->default('beginner');
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category_id');
        });

        Schema::create('mission_template_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('mission_templates')->cascadeOnDelete();
            $table->foreignId('capability_type_id')->constrained('mission_capability_types')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('label')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'capability_type_id'], 'me_tpl_cap_unique');
        });

        Schema::create('mission_template_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('mission_templates')->cascadeOnDelete();
            $table->foreignId('capability_type_id')->nullable()->constrained('mission_capability_types')->nullOnDelete();
            $table->string('field_key');
            $table->json('label');
            $table->json('help_text')->nullable();
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('section')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'field_key'], 'me_tpl_field_unique');
        });

        Schema::create('mission_template_phases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('mission_templates')->cascadeOnDelete();
            $table->string('slug');
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('required_completion_count')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'slug'], 'me_tpl_phase_slug_unique');
        });

        Schema::create('mission_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('mission_templates')->restrictOnDelete();
            $table->foreignId('current_phase_id')->nullable()->constrained('mission_template_phases')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status')->default('active');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('template_snapshot');
            $table->json('field_values')->nullable();
            $table->json('reminder_settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('template_id');
        });

        Schema::create('mission_measurements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->string('metric_key');
            $table->decimal('value', 12, 4);
            $table->string('unit')->nullable();
            $table->boolean('is_goal')->default(false);
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'metric_key', 'measured_at']);
        });

        Schema::create('mission_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['enrollment_id', 'logged_at']);
        });

        Schema::create('mission_media', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->string('field_key')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('enrollment_id');
        });

        Schema::create('mission_workout_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->date('session_date');
            $table->string('day_key', 8)->nullable();
            $table->string('focus', 120)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'session_date'], 'me_workout_session_date_unique');
            $table->index(['enrollment_id', 'session_date']);
        });

        Schema::create('mission_workout_exercises', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workout_session_id')->constrained('mission_workout_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('workout_session_id');
        });

        Schema::create('mission_workout_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained('mission_workout_exercises')->cascadeOnDelete();
            $table->unsignedTinyInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('weight_unit', 8)->default('kg');
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->decimal('rpe', 3, 1)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['workout_exercise_id', 'set_number']);
        });

        Schema::create('mission_nutrition_days', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->date('log_date');
            $table->unsignedInteger('total_calories')->nullable();
            $table->string('calories_status', 24)->nullable();
            $table->unsignedTinyInteger('meal_quality_score')->nullable();
            $table->text('day_notes')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'log_date'], 'me_nutrition_day_unique');
            $table->index(['enrollment_id', 'log_date']);
        });

        Schema::create('mission_meals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('nutrition_day_id')->constrained('mission_nutrition_days')->cascadeOnDelete();
            $table->string('meal_type', 24);
            $table->time('meal_time')->nullable();
            $table->unsignedInteger('meal_calories')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['nutrition_day_id', 'meal_type']);
        });

        Schema::create('mission_meal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_id')->constrained('mission_meals')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->decimal('protein_g', 6, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('meal_id');
        });

        Schema::create('mission_supplement_products', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('default_unit', 32)->default('scoop');
            $table->string('default_amount', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['enrollment_id', 'is_active']);
        });

        Schema::create('mission_supplement_intakes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->foreignId('supplement_product_id')->nullable()->constrained('mission_supplement_products')->nullOnDelete();
            $table->string('product_name');
            $table->string('brand')->nullable();
            $table->decimal('amount', 8, 2);
            $table->string('unit', 32);
            $table->date('intake_date');
            $table->time('intake_time')->nullable();
            $table->boolean('taken')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'intake_date']);
        });

        Schema::create('mission_daily_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->date('report_date');
            $table->decimal('body_weight', 8, 2)->nullable();
            $table->unsignedTinyInteger('mood_score')->nullable();
            $table->unsignedTinyInteger('energy_score')->nullable();
            $table->decimal('sleep_hours', 4, 2)->nullable();
            $table->boolean('trained_today')->default(false);
            $table->boolean('nutrition_logged')->default(false);
            $table->text('highlights')->nullable();
            $table->text('challenges')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('workout_session_id')->nullable()->constrained('mission_workout_sessions')->nullOnDelete();
            $table->foreignId('nutrition_day_id')->nullable()->constrained('mission_nutrition_days')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'report_date'], 'me_daily_report_unique');
            $table->index(['enrollment_id', 'report_date']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX mission_enrollments_field_values_gin ON mission_enrollments USING GIN (field_values jsonb_path_ops)');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS mission_enrollments_field_values_gin');
        }

        Schema::dropIfExists('mission_daily_reports');
        Schema::dropIfExists('mission_supplement_intakes');
        Schema::dropIfExists('mission_supplement_products');
        Schema::dropIfExists('mission_meal_items');
        Schema::dropIfExists('mission_meals');
        Schema::dropIfExists('mission_nutrition_days');
        Schema::dropIfExists('mission_workout_sets');
        Schema::dropIfExists('mission_workout_exercises');
        Schema::dropIfExists('mission_workout_sessions');
        Schema::dropIfExists('mission_media');
        Schema::dropIfExists('mission_activity_logs');
        Schema::dropIfExists('mission_measurements');
        Schema::dropIfExists('mission_enrollments');
        Schema::dropIfExists('mission_template_phases');
        Schema::dropIfExists('mission_template_fields');
        Schema::dropIfExists('mission_template_capabilities');
        Schema::dropIfExists('mission_templates');
        Schema::dropIfExists('mission_capability_types');
        Schema::dropIfExists('mission_categories');
    }
};
