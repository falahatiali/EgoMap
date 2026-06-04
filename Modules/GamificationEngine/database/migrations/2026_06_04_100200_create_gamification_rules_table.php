<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event-driven reward/penalty rules (conditions + effects JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_rules')) {
            return;
        }

        Schema::create('gamification_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('event', 80)->index();
            $table->string('rule_type', 20);
            $table->json('conditions')->nullable();
            $table->json('effects');
            $table->unsignedSmallInteger('max_per_day')->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_rules');
    }
};
