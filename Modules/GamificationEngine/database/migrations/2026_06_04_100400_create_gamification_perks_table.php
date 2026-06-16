<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perk catalogue (consumable or permanent; granted by rules or shop).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_perks', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 40)->default('fa-gift');
            $table->string('type', 20)->default('consumable');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_perks');
    }
};
