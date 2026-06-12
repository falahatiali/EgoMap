<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_program_exercise_sets')) {
            return;
        }

        Schema::create('aether_program_exercise_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_program_exercise_id')
                ->constrained('aether_program_exercises', indexName: 'apes_exercise_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('set_number');
            $table->string('set_type', 24)->default('working');
            $table->unsignedTinyInteger('target_reps_min')->nullable();
            $table->unsignedTinyInteger('target_reps_max')->nullable();
            $table->decimal('target_weight_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('target_rpe')->nullable();
            $table->unsignedTinyInteger('target_rir')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->default(90);
            $table->string('tempo', 16)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['aether_program_exercise_id', 'set_number'], 'apes_ex_set_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_exercise_sets');
    }
};
