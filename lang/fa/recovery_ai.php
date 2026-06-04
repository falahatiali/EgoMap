<?php

return [

    'default_attachment_pattern' => 'الگوی دلبستگی هنوز در حال شکل‌گیری است',

    'fallback' => [
        'truth_flashes' => [
            'تماس در این لحظه احتمالاً آرامش نمی‌دهد.',
            'تو بیشتر به یک الگو وابسته بودی تا به یک واقعیت.',
            'ساختن دوباره از فاصله شروع می‌شود، نه از یک پیام.',
        ],
        'truth_contact' => 'تماس امشب احتمالاً آرامش نمی‌دهد — فقط موج را دوباره راه می‌اندازد.',
        'emergency' => [
            'message' => 'این لحظه بحرانی است. الان تصمیم نگیر. احساس تماس فوری، ترک دلبستگی است — نه یک دستور.',
            'exercise' => '۴ ثانیه دم، ۶ ثانیه بازدم — ده بار. بعد ۱۰ دراز و نشست.',
        ],
        'blackhole' => [
            'dominant_emotions' => 'دلتنگی، اضطراب، التماس',
            'analysis' => 'این متن بیشتر احساس فوری را منتقل می‌کند تا یک مرز سالم. ارسالش احتمالاً فردا پشیمانی می‌آورد.',
            'rewrite_suggestion' => 'توصیه: ارسال نکن. اگر واقعاً لازم است، فقط یک جمله خنثی درباره یک موضوع عملی بنویس — بدون احساس.',
            'closing_line' => 'این متن در سیاه‌چاله نابود شد. نفس بکش.',
        ],
    ],

    'slip' => [
        'recovery_task' => '۱۰ دراز و نشست یا ۵ دقیقه تنفس عمیق — سپر را ترمیم کن.',
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
TEXT,
        ],
    ],

];
