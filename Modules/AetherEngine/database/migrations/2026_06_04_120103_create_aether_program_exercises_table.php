<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_program_workout_day_id')
                ->constrained('aether_program_workout_days', indexName: 'ap_ex_wday_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('slug');
            $table->string('name');
            $table->string('muscle_group', 32);
            $table->unsignedTinyInteger('sets');
            $table->string('reps', 32);
            $table->unsignedSmallInteger('rest_seconds')->default(90);
            $table->text('notes')->nullable();
            $table->json('alternative_slugs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_exercises');
    }
};
