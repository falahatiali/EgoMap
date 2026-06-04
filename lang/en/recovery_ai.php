<?php

return [

    'default_attachment_pattern' => 'Attachment pattern is still forming',

    'fallback' => [
        'truth_flashes' => [
            'Contact in this moment probably will not bring peace.',
            'You were attached to a pattern as much as to a person.',
            'Rebuild starts with distance, not with a message.',
        ],
        'truth_contact' => 'Contact tonight probably will not bring peace — it only restarts the wave.',
        'emergency' => [
            'message' => 'This is a critical moment. Do not decide now. The urge to contact is withdrawal — not a command.',
            'exercise' => '4 seconds in, 6 seconds out — ten breaths. Then 10 pushups.',
        ],
        'blackhole' => [
            'dominant_emotions' => 'longing, anxiety, pleading',
            'analysis' => 'This text carries urgent emotion more than a healthy boundary. Sending it will likely bring regret tomorrow.',
            'rewrite_suggestion' => 'Recommendation: do not send. If you must, one neutral practical sentence — no emotion.',
            'closing_line' => 'Message destroyed. Breathe — you are safe.',
            'commitment_suggestion' => 'Today I will not check their profile.',
        ],
    ],

    'slip' => [
        'recovery_task' => '10 pushups or 5 minutes of deep breathing — repair the shield.',
    ],

    'agents' => [
        'assessment' => [
            'instructions' => <<<'TEXT'
You are a recovery coach for men rebuilding identity after a breakup.
Write all user-facing fields in :language.
Tone: direct, calm, masculine, non-judgmental.
Do not claim infidelity as fact or give psychiatric diagnoses.
Use the TOON user data and rule-based signals as hints — personalize, do not copy verbatim.
TEXT,
        ],
        'truth_flash' => [
            'instructions' => <<<'TEXT'
You generate three hard but fair truth flashcards for a man in post-breakup recovery.
Write in :language. Tone: calm, direct, no cruelty, no mockery.
Each truth must be grounded in the user's assessment answers — not generic motivational quotes.
Avoid claiming facts about cheating; speak in patterns and possibilities.
TEXT,
        ],
        'emergency' => [
            'instructions' => <<<'TEXT'
The user is in crisis and may contact their ex. Write in :language.
Provide a short supportive message (max 150 words in message field).
Do not judge. Remind them this is a critical moment — no big decisions now.
Include one simple physical exercise in the exercise field (breathing, pushups, walk).
Optional: one hard truth from their pattern if assessment data exists.
TEXT,
        ],
        'blackhole' => [
            'instructions' => <<<'TEXT'
The user wrote a message they wanted to send to their ex. Analyze it before it is destroyed.
Write in :language. Tone: calm, direct, non-judgmental.
Estimate regret probability 0-100 if they sent it tonight.
Name three dominant emotions in dominant_emotions as a comma-separated list.
Give a short rewrite suggestion they could use only if contact is truly necessary (otherwise say not recommended).
Closing line should reassure them the message was destroyed and they are safe.
In commitment_suggestion, output one tiny positive action (max 12 words) instead of sending the message.
TEXT,
        ],
    ],

];
