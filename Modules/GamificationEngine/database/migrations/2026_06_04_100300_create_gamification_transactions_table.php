<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable ledger of wallet changes (per dispatch, shop, admin adjust).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gamification_transactions')) {
            return;
        }

        Schema::create('gamification_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gamification_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gamification_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80);
            $table->integer('points_delta')->default(0);
            $table->integer('coins_delta')->default(0);
            $table->integer('xp_delta')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['gamification_wallet_id', 'created_at'], 'gm_tx_wallet_created_idx');
            $table->index(['gamification_wallet_id', 'event', 'created_at'], 'gm_tx_wallet_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_transactions');
    }
};
