<?php

return [

    'xp_per_level' => 100,

    'max_level' => 100,

    /*
    |--------------------------------------------------------------------------
    | Premium upsell (Ghost Mode)
    |--------------------------------------------------------------------------
    | min_clean_streak_days: show when gamification streak_days >= this value (day 4 = 3).
    | premium_upsell_day: alias for documentation / admin (same as min + 1).
    */
    'premium_upsell' => [
        'min_clean_streak_days' => (int) env('GAMIFICATION_PREMIUM_UPSELL_MIN_STREAK', 3),
        'premium_upsell_day' => (int) env('GAMIFICATION_PREMIUM_UPSELL_DAY', 4),
        'defer_days' => (int) env('GAMIFICATION_PREMIUM_UPSELL_DEFER_DAYS', 3),
        'first_discount_percent' => (int) env('GAMIFICATION_PREMIUM_UPSELL_DISCOUNT_FIRST', 40),
        'reminder_discount_percent' => (int) env('GAMIFICATION_PREMIUM_UPSELL_DISCOUNT_REMINDER', 25),
        'base_price_usd' => (float) env('GAMIFICATION_PREMIUM_UPSELL_BASE_PRICE', 15),
        'coupon_first' => env('GAMIFICATION_PREMIUM_UPSELL_COUPON_FIRST', 'UPSELL40'),
        'coupon_reminder' => env('GAMIFICATION_PREMIUM_UPSELL_COUPON_REMINDER', 'UPSELL25'),
        'pricing_anchor' => '#pricing',
    ],

    'punishments' => [
        'suggestions_count' => 4,
        'physical_max_per_day' => 2,
        'physical_cooldown_hours' => 2,
        'recovery_percent' => 50,
        'recovery_points_by_severity' => [
            1 => 3,
            2 => 5,
            3 => 8,
        ],
        'recovery_coins_by_severity' => [
            1 => 1,
            2 => 2,
            3 => 3,
        ],
        'missed_checkin_gentle_days' => 3,
        'slip_severity' => [
            'checked_profile' => 2,
            'sent_message' => 3,
            'felt_weak' => 1,
            'other' => 1,
        ],
    ],

    'ghost_mode' => [
        'daily_mission' => [
            'points' => 8,
            'coins' => 3,
            'xp' => 10,
        ],
        'block_confirmed' => [
            'points' => 5,
            'coins' => 2,
        ],
        'panic_button' => [
            'points' => 5,
            'coins' => 1,
        ],
        'daily_quotes' => [
            'Silence is not weakness — it is strategy.',
            'Every day you do not reach out, you reclaim a piece of yourself.',
            'The urge will pass. Your dignity will stay.',
            'You are building a life where her opinion is not your oxygen.',
            'Ghost Mode is not hiding — it is healing in public with yourself.',
            'Discipline today is the love letter your future self is waiting for.',
        ],
    ],

];
