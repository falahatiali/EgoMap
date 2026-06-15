<?php

return [
    'ai_coaching_enabled' => (bool) env('VIRTUE_AI_COACHING_ENABLED', true),

    'goal_types' => [
        'days_count' => ['default_target' => 21, 'label' => '21-Day Challenge'],
        'success_count' => ['default_target' => 30, 'label' => '30 Successes'],
    ],

    'points' => [
        'success_logged' => 5,
        'streak_7' => 30,
        'routine_completed' => 200,
        'slip_honest' => 1,
    ],
];
