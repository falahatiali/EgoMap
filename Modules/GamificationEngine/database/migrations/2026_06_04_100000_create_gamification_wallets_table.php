<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User/guest wallet: points, coins, XP, level, streak, badges, perks.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_wallets')) {
            return;
        }

        Schema::create('gamification_wallets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->unique();
            $table->integer('points')->default(0);
            $table->unsignedInteger('coins')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedInteger('streak_days')->default(0);
            $table->json('badges')->nullable();
            $table->json('perks')->nullable();
            $table->json('metadata')->nullable();
            $table->date('last_login_date')->nullable();
            $table->timestamps();

            $table->index(['guest_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_wallets');
    }
};
