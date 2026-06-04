<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badge catalogue (slug referenced in rule/shop effects).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_badges')) {
            return;
        }

        Schema::create('gamification_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 40)->default('fa-medal');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_badges');
    }
};
