<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stripe_plans', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_price_id')->unique();
            $table->string('stripe_product_id');
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->text('description')->nullable();
            $table->string('currency', 3);
            $table->unsignedInteger('unit_amount')->nullable();
            $table->string('interval')->nullable();
            $table->unsignedSmallInteger('interval_count')->default(1);
            $table->string('billing_scheme')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->string('lookup_key')->nullable();
            $table->string('subscription_type')->default('default');
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['active', 'is_recurring']);
            $table->index('stripe_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_plans');
    }
};
