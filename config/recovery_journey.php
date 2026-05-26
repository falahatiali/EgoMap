<?php

use App\Enums\PrimaryStruggle;
use App\Enums\RecoveryPhase;

return [

    'struggle_phase' => [
        PrimaryStruggle::Stalking->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::GetBack->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::Anger->value => RecoveryPhase::Detox->value,
        PrimaryStruggle::Worthless->value => RecoveryPhase::Detox->value,
    ],

    'action_plans' => [
        PrimaryStruggle::Stalking->value => [
            'icon' => 'hourglass-half',
            'status_key' => 'recovery.plan_status_red',
            'diagnosis_title_key' => 'recovery.plan_diagnosis_stalking_title',
            'diagnosis_body_key' => 'recovery.plan_diagnosis_stalking_body',
            'priority_why_key' => 'recovery.plan_why_stalking',
        ],
        PrimaryStruggle::GetBack->value => [
            'icon' => 'hourglass-half',
            'status_key' => 'recovery.plan_status_red',
            'diagnosis_title_key' => 'recovery.plan_diagnosis_get_back_title',
            'diagnosis_body_key' => 'recovery.plan_diagnosis_get_back_body',
            'priority_why_key' => 'recovery.plan_why_get_back',
        ],
        PrimaryStruggle::Anger->value => [
            'icon' => 'hourglass-half',
            'status_key' => 'recovery.plan_status_red',
            'diagnosis_title_key' => 'recovery.plan_diagnosis_anger_title',
            'diagnosis_body_key' => 'recovery.plan_diagnosis_anger_body',
            'priority_why_key' => 'recovery.plan_why_anger',
        ],
        PrimaryStruggle::Worthless->value => [
            'icon' => 'hourglass-half',
            'status_key' => 'recovery.plan_status_red',
            'diagnosis_title_key' => 'recovery.plan_diagnosis_worthless_title',
            'diagnosis_body_key' => 'recovery.plan_diagnosis_worthless_body',
            'priority_why_key' => 'recovery.plan_why_worthless',
        ],
    ],

    'recommendations' => [
        PrimaryStruggle::Stalking->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_no_contact_title',
            'body_key' => 'recovery.rec_no_contact_body',
            'cta_key' => 'recovery.plan_activate_cta',
        ],
        PrimaryStruggle::GetBack->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_no_contact_title',
            'body_key' => 'recovery.rec_get_back_body',
            'cta_key' => 'recovery.plan_activate_cta',
        ],
        PrimaryStruggle::Anger->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_vent_title',
            'body_key' => 'recovery.rec_anger_body',
            'cta_key' => 'recovery.plan_activate_cta',
        ],
        PrimaryStruggle::Worthless->value => [
            'route' => 'no-contact',
            'icon' => 'hourglass-half',
            'title_key' => 'recovery.rec_no_contact_title',
            'body_key' => 'recovery.rec_worthless_body',
            'cta_key' => 'recovery.plan_activate_cta',
        ],
    ],

    'unlock_conditions' => [
        RecoveryPhase::Diagnose->value => 'recovery.unlock_diagnose',
        RecoveryPhase::Detox->value => 'recovery.unlock_detox',
        RecoveryPhase::Deliver->value => 'recovery.unlock_deliver',
    ],

];
