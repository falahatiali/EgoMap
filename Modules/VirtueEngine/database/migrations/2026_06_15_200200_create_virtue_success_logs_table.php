<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtue_success_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('virtue_routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('situation')->nullable();
            $table->string('emotional_state')->nullable();
            $table->string('ai_encouragement')->nullable();
            $table->unsignedTinyInteger('points_earned')->default(5);
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['virtue_routine_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtue_success_logs');
    }
};
