<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Suggested commitment lengths (days)
    |--------------------------------------------------------------------------
    */

    'recommended_days' => (int) env('NO_CONTACT_RECOMMENDED_DAYS', 90),

    'presets' => [
        [
            'days' => 30,
            'label_key' => 'no_contact.preset_30',
            'description_key' => 'no_contact.preset_30_desc',
        ],
        [
            'days' => 60,
            'label_key' => 'no_contact.preset_60',
            'description_key' => 'no_contact.preset_60_desc',
        ],
        [
            'days' => 90,
            'label_key' => 'no_contact.preset_90',
            'description_key' => 'no_contact.preset_90_desc',
            'recommended' => true,
        ],
        [
            'days' => 120,
            'label_key' => 'no_contact.preset_120',
            'description_key' => 'no_contact.preset_120_desc',
        ],
        [
            'days' => 180,
            'label_key' => 'no_contact.preset_180',
            'description_key' => 'no_contact.preset_180_desc',
        ],
    ],

];
