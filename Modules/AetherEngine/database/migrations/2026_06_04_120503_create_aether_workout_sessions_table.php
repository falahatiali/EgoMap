<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_workout_sessions')) {
            return;
        }

        Schema::create('aether_workout_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aether_generated_program_id')
                ->nullable()
                ->constrained('aether_generated_programs', indexName: 'aws_program_fk')
                ->nullOnDelete();
            $table->foreignId('aether_program_workout_day_id')
                ->nullable()
                ->constrained('aether_program_workout_days', indexName: 'aws_wday_fk')
                ->nullOnDelete();
            $table->date('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 24)->default('scheduled');
            $table->unsignedTinyInteger('energy_level')->nullable();
            $table->unsignedTinyInteger('mood_level')->nullable();
            $table->unsignedTinyInteger('pain_level')->nullable();
            $table->unsignedTinyInteger('difficulty_rating')->nullable();
            $table->text('user_feedback')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_for'], 'aws_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_workout_sessions');
    }
};
