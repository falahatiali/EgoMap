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
            $table->text('instructions')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('alternative_slugs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_exercises');
    }
};
