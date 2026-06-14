<?php

return [
    'name' => 'AetherEngine',

    'program_weeks' => 12,

    'ai_enrichment_enabled' => env('AETHER_AI_ENRICHMENT', false),

    'calorie_adjustments' => [
        'fat_loss' => -400,
        'muscle_gain' => 250,
        'recomposition' => 0,
        'strength' => -100,
        'endurance' => 0,
        'aesthetics' => -200,
        'health' => 0,
    ],

    'protein_g_per_kg' => [
        'default' => 1.6,
        'muscle_gain' => 2.2,
        'fat_loss' => 2.0,
        'recomposition' => 2.0,
    ],

    'fat_g_per_kg' => 0.9,

    'meals_per_day' => 4,

    'exercise_api' => [
        'media_strategy' => env('AETHER_EXERCISE_MEDIA_STRATEGY', 'fallback'),

        'exercise_gym_gifs_db' => [
            'enabled' => env('EXERCISE_GYM_GIFS_DB_ENABLED', true),
            'base_url' => env(
                'EXERCISE_GYM_GIFS_DB_BASE_URL',
                'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0',
            ),
            'language' => env('EXERCISE_GYM_GIFS_DB_LANGUAGE', 'en'),
            'priority' => (int) env('EXERCISE_GYM_GIFS_DB_MEDIA_PRIORITY', 0),
            'cache_ttl' => (int) env('EXERCISE_GYM_GIFS_DB_CACHE_TTL', 86400),
            'timeout' => (int) env('EXERCISE_GYM_GIFS_DB_TIMEOUT', 15),
            'connect_timeout' => (int) env('EXERCISE_GYM_GIFS_DB_CONNECT_TIMEOUT', 5),
        ],

        'musclewiki' => [
            'enabled' => env('MUSCLEWIKI_API_ENABLED', false),
            'key' => env('MUSCLEWIKI_API_KEY'),
            'base_url' => env('MUSCLEWIKI_API_BASE_URL', 'https://api.musclewiki.com'),
            'default_gender' => env('MUSCLEWIKI_DEFAULT_GENDER', 'male'),
            'priority' => (int) env('MUSCLEWIKI_MEDIA_PRIORITY', 1),
            'timeout' => (int) env('MUSCLEWIKI_API_TIMEOUT', 8),
            'connect_timeout' => (int) env('MUSCLEWIKI_API_CONNECT_TIMEOUT', 3),
            'retry_times' => (int) env('MUSCLEWIKI_API_RETRY_TIMES', 2),
            'retry_sleep_ms' => (int) env('MUSCLEWIKI_API_RETRY_SLEEP_MS', 250),
        ],

        'workoutx' => [
            'enabled' => env('WORKOUTX_API_ENABLED', false),
            'key' => env('WORKOUTX_API_KEY'),
            'base_url' => env('WORKOUTX_API_BASE_URL', 'https://api.workoutx.com/v1'),
            'priority' => (int) env('WORKOUTX_MEDIA_PRIORITY', 2),
            'timeout' => (int) env('WORKOUTX_API_TIMEOUT', 8),
            'connect_timeout' => (int) env('WORKOUTX_API_CONNECT_TIMEOUT', 3),
        ],
    ],

    'exercise_catalog' => [
        'default' => env('AETHER_EXERCISE_CATALOG_PROVIDER', 'exercise_gym_gifs_db'),
    ],
];
