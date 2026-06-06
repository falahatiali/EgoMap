<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_meal_templates')) {
            return;
        }

        Schema::create('aether_meal_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('meal_type', 16);
            $table->json('dietary_tags');
            $table->unsignedSmallInteger('calories');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('carbs_g');
            $table->unsignedSmallInteger('fat_g');
            $table->json('ingredients');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('prep_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_meal_templates');
    }
};
