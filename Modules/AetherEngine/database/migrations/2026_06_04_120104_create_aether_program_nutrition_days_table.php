<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_nutrition_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_generated_program_id')
                ->constrained('aether_generated_programs', indexName: 'ap_nday_program_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_index');
            $table->unsignedSmallInteger('total_calories')->default(0);
            $table->unsignedSmallInteger('total_protein')->default(0);
            $table->unsignedSmallInteger('total_carbs')->default(0);
            $table->unsignedSmallInteger('total_fat')->default(0);
            $table->text('tip')->nullable();
            $table->timestamps();

            $table->unique(['aether_generated_program_id', 'day_index'], 'aether_program_nutrition_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_nutrition_days');
    }
};
