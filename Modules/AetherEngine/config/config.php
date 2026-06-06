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
];
