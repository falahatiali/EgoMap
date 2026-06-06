<?php

namespace App\Services\Billing;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillingSchemaEnsurer
{
    /**
     * @return list<string>
     */
    public function ensure(): array
    {
        $applied = [];

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use (&$applied): void {
                if (! Schema::hasColumn('users', 'stripe_id')) {
                    $table->string('stripe_id')->nullable()->index();
                    $applied[] = 'users.stripe_id';
                }

                if (! Schema::hasColumn('users', 'pm_type')) {
                    $table->string('pm_type')->nullable();
                    $applied[] = 'users.pm_type';
                }

                if (! Schema::hasColumn('users', 'pm_last_four')) {
                    $table->string('pm_last_four', 4)->nullable();
                    $applied[] = 'users.pm_last_four';
                }

                if (! Schema::hasColumn('users', 'trial_ends_at')) {
                    $table->timestamp('trial_ends_at')->nullable();
                    $applied[] = 'users.trial_ends_at';
                }
            });
        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->string('type');
                $table->string('stripe_id')->unique();
                $table->string('stripe_status');
                $table->string('stripe_price')->nullable();
                $table->integer('quantity')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'stripe_status']);
            });

            $applied[] = 'subscriptions';
        }

        if (! Schema::hasTable('subscription_items')) {
            Schema::create('subscription_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('subscription_id');
                $table->string('stripe_id')->unique();
                $table->string('stripe_product');
                $table->string('stripe_price');
                $table->string('meter_id')->nullable();
                $table->integer('quantity')->nullable();
                $table->string('meter_event_name')->nullable();
                $table->timestamps();

                $table->index(['subscription_id', 'stripe_price']);
            });

            $applied[] = 'subscription_items';
        }

        if (! Schema::hasTable('stripe_plans')) {
            Schema::create('stripe_plans', function (Blueprint $table): void {
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
            });

            $applied[] = 'stripe_plans';
        }

        return $applied;
    }
}
