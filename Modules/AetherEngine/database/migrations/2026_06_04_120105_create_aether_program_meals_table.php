<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_meals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_program_nutrition_day_id')
                ->constrained('aether_program_nutrition_days', indexName: 'ap_meal_nday_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('meal_type', 16);
            $table->string('name');
            $table->unsignedSmallInteger('calories')->default(0);
            $table->unsignedSmallInteger('protein_grams')->default(0);
            $table->unsignedSmallInteger('carb_grams')->default(0);
            $table->unsignedSmallInteger('fat_grams')->default(0);
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_meals');
    }
};
