<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phoenix Shop: coin-priced items with typed effects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_shop_items', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 40)->default('fa-bag-shopping');
            $table->unsignedInteger('cost_coins');
            $table->string('effect_type', 40);
            $table->json('effects');
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_shop_items');
    }
};
