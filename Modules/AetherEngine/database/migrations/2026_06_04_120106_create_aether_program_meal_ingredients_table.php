<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aether_program_meal_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aether_program_meal_id')
                ->constrained('aether_program_meals', indexName: 'ap_ing_meal_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('name');
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 16)->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->decimal('protein_g', 6, 2)->nullable();
            $table->decimal('carbs_g', 6, 2)->nullable();
            $table->decimal('fat_g', 6, 2)->nullable();
            $table->string('category', 32)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_meal_ingredients');
    }
};
