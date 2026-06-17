<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mood_entries')) {
            return;
        }

        Schema::create('mood_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emotion', 32);
            $table->unsignedTinyInteger('intensity');
            $table->json('ai_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_ideas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mood_entry_id')->nullable()->constrained('mood_entries')->nullOnDelete();
            $table->string('seed_text');
            $table->string('source', 32)->default('manual');
            $table->string('status', 32)->default('raw');
            $table->json('matured_details')->nullable();
            $table->string('goal_cadence', 32)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('harvested_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ideas');
        Schema::dropIfExists('mood_entries');
    }
};
