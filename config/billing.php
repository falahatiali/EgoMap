<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription name
    |--------------------------------------------------------------------------
    |
    | Cashier subscription type stored in the subscriptions.type column.
    | Use a single active subscription of this type per customer.
    |
    */

    'subscription_name' => env('BILLING_SUBSCRIPTION_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Stripe price IDs
    |--------------------------------------------------------------------------
    |
    | Set these after creating products/prices in Stripe (or via sync).
    | Used for checkout and subscription checks.
    |
    */

    'prices' => [
        'monthly' => env('STRIPE_PRICE_MONTHLY'),
        'yearly' => env('STRIPE_PRICE_YEARLY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Pro role from Stripe
    |--------------------------------------------------------------------------
    |
    | When enabled, webhook handlers assign/remove the "pro" role to match
    | an active Stripe subscription (in addition to isPro() subscription checks).
    |
    */

    'sync_pro_role' => (bool) env('BILLING_SYNC_PRO_ROLE', true),

];
