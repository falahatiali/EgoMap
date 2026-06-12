<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_program_exercise_overrides')) {
            return;
        }

        Schema::create('aether_program_exercise_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aether_program_exercise_id')
                ->constrained('aether_program_exercises', indexName: 'apeo_exercise_fk')
                ->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('muscle_group', 32);
            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps', 32)->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'aether_program_exercise_id'], 'apeo_user_ex_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_exercise_overrides');
    }
};
