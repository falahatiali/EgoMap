<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_punishments')) {
            return;
        }

        Schema::create('gamification_punishments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->string('difficulty', 20);
            $table->integer('points')->default(0);
            $table->integer('coins')->default(0);
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            $table->unsignedTinyInteger('min_slip_severity')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'type', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_punishments');
    }
};
