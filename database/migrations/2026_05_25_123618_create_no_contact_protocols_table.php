<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('no_contact_protocols', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->index();
            $table->unsignedSmallInteger('duration_days');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('streak_started_at');
            $table->timestamp('target_ends_at')->index();
            $table->unsignedInteger('slip_count')->default(0);
            $table->timestamp('last_slip_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('gamification_rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['guest_token', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('no_contact_protocols');
    }
};
