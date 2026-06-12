<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_workout_set_logs')) {
            return;
        }

        Schema::create('aether_workout_set_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aether_workout_session_id')
                ->constrained('aether_workout_sessions', indexName: 'awsl_session_fk')
                ->cascadeOnDelete();
            $table->foreignId('aether_program_exercise_set_id')
                ->constrained('aether_program_exercise_sets', indexName: 'awsl_ex_set_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('completed_reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('perceived_exertion')->nullable();
            $table->unsignedTinyInteger('pain_level')->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('skipped')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'aether_workout_session_id', 'aether_program_exercise_set_id'], 'awsl_sess_set_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_workout_set_logs');
    }
};
