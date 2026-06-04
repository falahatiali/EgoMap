<?php

return [

    'prompt_version' => 'recovery-v1',

    /*
    |--------------------------------------------------------------------------
    | Optional model override
    |--------------------------------------------------------------------------
    |
    | Leave null to use the default model for config('ai.default').
    | Set RECOVERY_AI_MODEL when you need a specific model name.
    |
    */

    'model' => env('RECOVERY_AI_MODEL'),

    'slip_penalty_days' => (int) env('GHOST_MODE_SLIP_PENALTY_DAYS', 5),

    'slip_triggers' => [
        'checked_profile',
        'sent_message',
        'felt_weak',
        'other',
    ],

    'emergency_breath_seconds' => 60,

];
