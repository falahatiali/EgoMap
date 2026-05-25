<?php

use App\Enums\PrimaryStruggle;
use App\Enums\RecoveryPhase;

return [

    'struggle_phase' => [
        PrimaryStruggle::Stalking->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::GetBack->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::Anger->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::Worthless->value => RecoveryPhase::Diagnose->value,
    ],

    'recommendations' => [
        PrimaryStruggle::Stalking->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_no_contact_title',
            'body_key' => 'recovery.rec_no_contact_body',
            'cta_key' => 'recovery.rec_no_contact_cta',
        ],
        PrimaryStruggle::GetBack->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_no_contact_title',
            'body_key' => 'recovery.rec_get_back_body',
            'cta_key' => 'recovery.rec_no_contact_cta',
        ],
        PrimaryStruggle::Anger->value => [
            'route' => 'no-contact',
            'icon' => 'comments',
            'title_key' => 'recovery.rec_vent_title',
            'body_key' => 'recovery.rec_anger_body',
            'cta_key' => 'recovery.rec_no_contact_cta',
        ],
        PrimaryStruggle::Worthless->value => [
            'route' => 'quiz.start',
            'route_params' => ['slug' => 'mbti-personality'],
            'icon' => 'flask',
            'title_key' => 'recovery.rec_diagnose_title',
            'body_key' => 'recovery.rec_worthless_body',
            'cta_key' => 'recovery.rec_diagnose_cta',
        ],
    ],

    'unlock_conditions' => [
        RecoveryPhase::Diagnose->value => 'recovery.unlock_diagnose',
        RecoveryPhase::Detox->value => 'recovery.unlock_detox',
        RecoveryPhase::Deliver->value => 'recovery.unlock_deliver',
    ],

];
