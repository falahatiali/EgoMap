<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_user_punishments')) {
            return;
        }

        Schema::create('gamification_user_punishments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gamification_punishment_id')->constrained('gamification_punishments')->cascadeOnDelete();
            $table->foreignId('no_contact_protocol_id')->nullable()->constrained('no_contact_protocols')->nullOnDelete();
            $table->string('slip_trigger', 40)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('assigned_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_user_punishments');
    }
};
