<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_workout_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_generated_program_id')
                ->constrained('aether_generated_programs', indexName: 'ap_wday_program_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_index');
            $table->string('label');
            $table->string('focus');
            $table->text('warmup')->nullable();
            $table->text('cooldown')->nullable();
            $table->text('motivation')->nullable();
            $table->timestamps();

            $table->unique(['aether_generated_program_id', 'day_index'], 'aether_program_workout_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_workout_days');
    }
};
