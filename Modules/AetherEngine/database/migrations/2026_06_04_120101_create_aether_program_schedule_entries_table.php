<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_schedule_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_generated_program_id')
                ->constrained('aether_generated_programs', indexName: 'ap_sched_program_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('iso_weekday');
            $table->string('entry_type', 16);
            $table->unsignedTinyInteger('workout_day_index')->nullable();
            $table->text('meal_timing_note')->nullable();
            $table->timestamps();

            $table->unique(['aether_generated_program_id', 'iso_weekday'], 'aether_program_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_schedule_entries');
    }
};
