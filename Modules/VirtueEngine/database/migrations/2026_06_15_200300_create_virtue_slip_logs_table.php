<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtue_slip_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('virtue_routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('what_happened')->nullable();
            $table->string('ai_personalized_punishment')->nullable();
            $table->unsignedBigInteger('gamification_user_punishment_id')->nullable();
            $table->boolean('punishment_completed')->default(false);
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['virtue_routine_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtue_slip_logs');
    }
};
