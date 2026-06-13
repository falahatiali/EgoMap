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

    /*
    |--------------------------------------------------------------------------
    | Mobile checkout return URLs
    |--------------------------------------------------------------------------
    |
    | Used by the billing API when starting Stripe Checkout from the mobile app.
    | {CHECKOUT_SESSION_ID} is replaced by Stripe on redirect.
    |
    */

    'mobile_checkout_success_url' => env(
        'BILLING_MOBILE_CHECKOUT_SUCCESS_URL',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/billing/app-return?checkout=success&session_id={CHECKOUT_SESSION_ID}',
    ),

    'mobile_checkout_cancel_url' => env(
        'BILLING_MOBILE_CHECKOUT_CANCEL_URL',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/billing/app-return?checkout=cancelled',
    ),

];
