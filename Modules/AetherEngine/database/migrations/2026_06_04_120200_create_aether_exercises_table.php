<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_exercises')) {
            return;
        }

        Schema::create('aether_exercises', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('muscle_group', 32);
            $table->json('equipment_required');
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->string('movement_pattern', 32)->default('compound');
            $table->text('instructions')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('alternative_slugs')->nullable();
            $table->string('gif_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('api_source', 32)->nullable();
            $table->string('api_external_id')->nullable();
            $table->timestamp('media_cached_at')->nullable();
            $table->string('rpe_range', 8)->default('6-8');
            $table->string('tempo', 16)->default('2-0-2-0');
            $table->decimal('default_weight_beginner_kg', 5, 1)->default(0);
            $table->decimal('default_weight_intermediate_kg', 5, 1)->default(0);
            $table->decimal('default_weight_advanced_kg', 5, 1)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_exercises');
    }
};
