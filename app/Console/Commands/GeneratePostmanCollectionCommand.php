<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'postman:generate', description: 'Generate Postman collection and environments from API definitions')]
class GeneratePostmanCollectionCommand extends Command
{
    protected $signature = 'postman:generate {--output=postman : Output directory relative to project root}';

    public function handle(): int
    {
        $outputDir = base_path($this->option('output'));

        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $collection = $this->buildCollection();
        $devEnv = $this->buildEnvironment('EgoMap — Development', $this->developmentVariables());
        $prodEnv = $this->buildEnvironment('EgoMap — Production', $this->productionVariables());

        File::put($outputDir.'/EgoMap.postman_collection.json', json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        File::put($outputDir.'/EgoMap-Development.postman_environment.json', json_encode($devEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        File::put($outputDir.'/EgoMap-Production.postman_environment.json', json_encode($prodEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->components->info('Postman files generated in '.$outputDir);
        $this->components->bulletList([
            'EgoMap.postman_collection.json',
            'EgoMap-Development.postman_environment.json',
            'EgoMap-Production.postman_environment.json',
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCollection(): array
    {
        return [
            'info' => [
                '_postman_id' => 'egomap-api-collection',
                'name' => 'EgoMap API',
                'description' => "Complete EgoMap JSON API (v1) including all enabled modules.\n\n"
                    ."**Setup:** Import collection + both environment files. Select *EgoMap — Development* or *EgoMap — Production* in Postman.\n\n"
                    ."**Auth flow:** Run *Auth → Login* (or *Verify Email*). Token is saved to `bearer_token` automatically.\n\n"
                    ."**Guest flows:** Quiz and Ghost Mode requests auto-save `guest_token` from responses.\n\n"
                    .'Regenerate: `php artisan postman:generate`',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{bearer_token}}', 'type' => 'string'],
                ],
            ],
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => $this->collectionPreRequestScript(),
                    ],
                ],
            ],
            'item' => $this->folders(),
        ];
    }

    /**
     * @return list<string>
     */
    private function collectionPreRequestScript(): array
    {
        return [
            "pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });",
            "const locale = pm.environment.get('accept_language') || pm.collectionVariables.get('accept_language') || 'en';",
            "pm.request.headers.upsert({ key: 'Accept-Language', value: locale });",
            "const guest = pm.environment.get('guest_token');",
            "if (guest) { pm.request.headers.upsert({ key: 'X-Guest-Token', value: guest }); }",
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function folders(): array
    {
        $folders = [];

        foreach ($this->endpointGroups() as $groupName => $endpoints) {
            $folders[] = [
                'name' => $groupName,
                'item' => array_map(fn (array $endpoint): array => $this->buildRequest($endpoint), $endpoints),
            ];
        }

        return $folders;
    }

    /**
     * @param  array<string, mixed>  $endpoint
     * @return array<string, mixed>
     */
    private function buildRequest(array $endpoint): array
    {
        $request = [
            'method' => $endpoint['method'],
            'header' => [],
            'url' => '{{base_url}}'.$endpoint['path'],
            'description' => $endpoint['description'] ?? '',
        ];

        if (! empty($endpoint['query'])) {
            $request['url'] = [
                'raw' => '{{base_url}}'.$endpoint['path'],
                'host' => ['{{base_url}}'],
                'path' => array_values(array_filter(explode('/', ltrim($endpoint['path'], '/')))),
                'query' => array_map(
                    fn (array $q): array => ['key' => $q['key'], 'value' => $q['value'], 'description' => $q['description'] ?? ''],
                    $endpoint['query'],
                ),
            ];
        }

        if (! empty($endpoint['body'])) {
            $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            $request['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($endpoint['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        $item = [
            'name' => $endpoint['name'],
            'request' => $request,
        ];

        if (($endpoint['auth'] ?? 'inherit') === 'none') {
            $item['request']['auth'] = ['type' => 'noauth'];
        }

        if (! empty($endpoint['test_script'])) {
            $item['event'] = [
                [
                    'listen' => 'test',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => $endpoint['test_script'],
                    ],
                ],
            ];
        }

        return $item;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function endpointGroups(): array
    {
        $saveToken = [
            'if (pm.response.code === 200 || pm.response.code === 201) {',
            '    const json = pm.response.json();',
            "    if (json.token) { pm.environment.set('bearer_token', json.token); }",
            "    if (json.verification_token) { pm.environment.set('verification_token', json.verification_token); }",
            '}',
        ];

        $saveGuestToken = [
            'if (pm.response.code === 200 || pm.response.code === 201) {',
            '    const json = pm.response.json();',
            "    if (json.guest_token) { pm.environment.set('guest_token', json.guest_token); }",
            "    if (json.session && json.session.uuid) { pm.environment.set('quiz_session_uuid', json.session.uuid); }",
            '}',
        ];

        $calibrationBody = [
            'intent' => ['entry_tool_key' => 'task'],
            'targets' => ['workout'],
            'wizard' => [
                'age' => 28,
                'gender' => 'male',
                'height_cm' => 175,
                'weight_kg' => 75,
                'body_fat_percent' => null,
                'primary_goal' => 'muscle_gain',
                'training_days_per_week' => 4,
                'session_duration' => '45_60',
                'preferred_workout_time' => 'evening',
                'gym_days' => ['mon', 'tue', 'thu', 'sat'],
                'equipment' => 'full_gym',
                'injury_tags' => [],
                'dietary_pattern' => 'omnivore',
                'cooking_ability' => 'simple',
                'coaching_tone' => 'gentle',
                'motivation_style' => 'feeling_strong',
                'training_style' => 'heavy_weights',
            ],
            'commitment' => ['confirmed' => true],
        ];

        return [
            '00 — Infrastructure' => [
                [
                    'name' => 'Health Check',
                    'method' => 'GET',
                    'path' => '/up',
                    'auth' => 'none',
                    'description' => 'Laravel health endpoint (no `/api` prefix).',
                ],
                [
                    'name' => 'Stripe Webhook (Cashier)',
                    'method' => 'POST',
                    'path' => '/{{cashier_path}}/webhook',
                    'auth' => 'none',
                    'description' => 'Stripe → Cashier webhook. Requires valid `Stripe-Signature` header (use Stripe CLI: `stripe listen --forward-to {{base_url}}/stripe/webhook`). Not callable manually without a signed payload.',
                    'body' => ['id' => 'evt_example', 'type' => 'checkout.session.completed', 'data' => ['object' => []]],
                ],
            ],
            '01 — Bootstrap' => [
                [
                    'name' => 'Bootstrap',
                    'method' => 'GET',
                    'path' => '/api/v1/bootstrap',
                    'auth' => 'none',
                    'description' => 'Landing copy, featured quiz slug, auth labels.',
                ],
            ],
            '02 — Auth' => [
                [
                    'name' => 'Register',
                    'method' => 'POST',
                    'path' => '/api/v1/auth/register',
                    'auth' => 'none',
                    'body' => ['email' => '{{user_email}}', 'password' => '{{user_password}}'],
                    'description' => 'Creates account; returns verification_token. Email verification required before login.',
                    'test_script' => $saveToken,
                ],
                [
                    'name' => 'Login',
                    'method' => 'POST',
                    'path' => '/api/v1/auth/login',
                    'auth' => 'none',
                    'body' => ['email' => '{{user_email}}', 'password' => '{{user_password}}'],
                    'description' => 'Returns bearer token when email is verified. Rate limited: 5/min per email+IP.',
                    'test_script' => $saveToken,
                ],
                [
                    'name' => 'Verify Email',
                    'method' => 'POST',
                    'path' => '/api/v1/auth/verify-email',
                    'auth' => 'none',
                    'body' => ['verification_token' => '{{verification_token}}', 'code' => '{{verification_code}}'],
                    'description' => '4-digit code from email. Returns bearer token on success.',
                    'test_script' => $saveToken,
                ],
                [
                    'name' => 'Resend Verification',
                    'method' => 'POST',
                    'path' => '/api/v1/auth/resend-verification',
                    'auth' => 'none',
                    'body' => ['verification_token' => '{{verification_token}}'],
                ],
                [
                    'name' => 'Me',
                    'method' => 'GET',
                    'path' => '/api/v1/auth/me',
                    'description' => 'Current authenticated user.',
                ],
                [
                    'name' => 'Logout',
                    'method' => 'POST',
                    'path' => '/api/v1/auth/logout',
                    'description' => 'Revokes current access token.',
                ],
            ],
            '03 — Profile & Billing' => [
                [
                    'name' => 'Profile',
                    'method' => 'GET',
                    'path' => '/api/v1/profile',
                    'query' => [
                        ['key' => 'tests_filter', 'value' => '{{tests_filter}}', 'description' => 'all | in_progress | completed'],
                    ],
                ],
                [
                    'name' => 'Billing — Show',
                    'method' => 'GET',
                    'path' => '/api/v1/billing',
                ],
                [
                    'name' => 'Billing — Checkout',
                    'method' => 'POST',
                    'path' => '/api/v1/billing/checkout',
                    'body' => ['plan_id' => '{{plan_id}}', 'coupon' => ''],
                ],
                [
                    'name' => 'Billing — Confirm Checkout',
                    'method' => 'POST',
                    'path' => '/api/v1/billing/checkout/confirm',
                    'body' => ['session_id' => '{{stripe_checkout_session_id}}'],
                ],
            ],
            '04 — Quizzes' => [
                [
                    'name' => 'Show Quiz',
                    'method' => 'GET',
                    'path' => '/api/v1/quizzes/{{quiz_slug}}',
                    'auth' => 'none',
                ],
                [
                    'name' => 'Quiz Entry',
                    'method' => 'GET',
                    'path' => '/api/v1/quizzes/{{quiz_slug}}/entry',
                    'auth' => 'none',
                    'query' => [
                        ['key' => 'resume_uuid', 'value' => '{{quiz_session_uuid}}', 'description' => 'Optional session UUID to resume'],
                    ],
                    'test_script' => $saveGuestToken,
                ],
                [
                    'name' => 'Start Quiz Session',
                    'method' => 'POST',
                    'path' => '/api/v1/quizzes/{{quiz_slug}}/sessions',
                    'auth' => 'none',
                    'body' => ['resume_uuid' => '{{quiz_session_uuid}}'],
                    'test_script' => $saveGuestToken,
                ],
            ],
            '05 — Quiz Sessions' => [
                [
                    'name' => 'Show Session',
                    'method' => 'GET',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}',
                    'auth' => 'none',
                    'description' => 'Requires X-Guest-Token header (auto-set from environment).',
                ],
                [
                    'name' => 'Submit Answer',
                    'method' => 'POST',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/answers',
                    'auth' => 'none',
                    'body' => ['value' => 'option_a'],
                    'description' => 'value: string for single-choice, array for multiple-choice.',
                ],
                [
                    'name' => 'Safety Answer',
                    'method' => 'POST',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/safety-answer',
                    'auth' => 'none',
                    'body' => ['value' => 1],
                    'description' => 'value: integer 1–4.',
                ],
                [
                    'name' => 'Go Back',
                    'method' => 'POST',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/back',
                    'auth' => 'none',
                ],
                [
                    'name' => 'Result',
                    'method' => 'GET',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/result',
                    'auth' => 'none',
                ],
                [
                    'name' => 'Send Report',
                    'method' => 'POST',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/send-report',
                    'auth' => 'none',
                    'body' => ['email' => '{{user_email}}'],
                ],
                [
                    'name' => 'Reset After Crisis',
                    'method' => 'POST',
                    'path' => '/api/v1/quiz-sessions/{{quiz_session_uuid}}/reset-after-crisis',
                    'auth' => 'none',
                ],
            ],
            '06 — Ghost Mode' => [
                [
                    'name' => 'Show Ghost Mode',
                    'method' => 'GET',
                    'path' => '/api/v1/ghost-mode',
                    'auth' => 'none',
                ],
                [
                    'name' => 'Start Protocol',
                    'method' => 'POST',
                    'path' => '/api/v1/ghost-mode/protocol',
                    'auth' => 'none',
                    'body' => ['duration_days' => 30],
                    'test_script' => $saveGuestToken,
                ],
            ],
            '07 — Missions (App)' => [
                [
                    'name' => 'List Missions',
                    'method' => 'GET',
                    'path' => '/api/v1/missions',
                    'description' => 'Authenticated mission catalog (app MissionController).',
                ],
                [
                    'name' => 'Show Mission',
                    'method' => 'GET',
                    'path' => '/api/v1/missions/{{mission_slug}}',
                ],
                [
                    'name' => 'Enroll in Mission',
                    'method' => 'POST',
                    'path' => '/api/v1/missions/{{mission_slug}}/enroll',
                    'test_script' => [
                        'if (pm.response.code === 200 || pm.response.code === 201) {',
                        '    const json = pm.response.json();',
                        '    const uuid = json.enrollment?.uuid || json.workspace?.enrollment?.uuid;',
                        "    if (uuid) { pm.environment.set('mission_enrollment_uuid', uuid); }",
                        '}',
                    ],
                ],
                [
                    'name' => 'Mission Workspace',
                    'method' => 'GET',
                    'path' => '/api/v1/missions/enrollments/{{mission_enrollment_uuid}}',
                ],
            ],
            '08 — Mission Engine (Enrollments)' => [
                [
                    'name' => 'List Enrollments',
                    'method' => 'GET',
                    'path' => '/api/v1/mission-enrollments',
                ],
                [
                    'name' => 'Show Enrollment',
                    'method' => 'GET',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}',
                ],
                [
                    'name' => 'Update Fields',
                    'method' => 'PATCH',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/fields',
                    'body' => ['fields' => ['body_weight' => 75]],
                ],
                [
                    'name' => 'Get Daily Report',
                    'method' => 'GET',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/daily-reports',
                    'query' => [
                        ['key' => 'date', 'value' => '{{report_date}}', 'description' => 'Required date (Y-m-d)'],
                    ],
                ],
                [
                    'name' => 'Save Daily Report',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/daily-reports',
                    'body' => [
                        'report_date' => '{{report_date}}',
                        'body_weight' => 75,
                        'mood_score' => 7,
                        'energy_score' => 6,
                        'sleep_hours' => 7.5,
                        'trained_today' => true,
                        'nutrition_logged' => true,
                        'highlights' => 'Good session',
                        'challenges' => '',
                        'notes' => '',
                    ],
                ],
                [
                    'name' => 'Add Supplement Product',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/supplements/products',
                    'body' => [
                        'name' => 'Whey Protein',
                        'brand' => 'Optimum',
                        'default_unit' => 'scoop',
                        'default_amount' => '1',
                    ],
                ],
                [
                    'name' => 'Log Supplement Intake',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/supplements/intakes',
                    'body' => [
                        'intake_date' => '{{report_date}}',
                        'supplement_product_id' => null,
                        'product_name' => 'Whey Protein',
                        'brand' => 'Optimum',
                        'amount' => 1,
                        'unit' => 'scoop',
                        'notes' => '',
                    ],
                ],
                [
                    'name' => 'Calibration Defaults',
                    'method' => 'GET',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/calibration/defaults',
                ],
                [
                    'name' => 'Calibration Complete',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/calibration/complete',
                    'body' => $calibrationBody,
                ],
                [
                    'name' => 'Calibration Regenerate',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/calibration/regenerate',
                    'body' => array_merge($calibrationBody, ['force' => true]),
                ],
                [
                    'name' => 'Generate Program',
                    'method' => 'POST',
                    'path' => '/api/v1/mission-enrollments/{{mission_enrollment_uuid}}/programs/generate',
                    'body' => [
                        'applied_target' => 'workout',
                        'wizard' => $calibrationBody['wizard'],
                    ],
                    'test_script' => [
                        'if (pm.response.code === 200 || pm.response.code === 201) {',
                        '    const json = pm.response.json();',
                        '    const uuid = json.program?.uuid || json.data?.uuid;',
                        "    if (uuid) { pm.environment.set('aether_program_uuid', uuid); }",
                        '}',
                    ],
                ],
            ],
            '09 — Gamification Engine' => [
                [
                    'name' => 'Wallet',
                    'method' => 'GET',
                    'path' => '/api/v1/gamification/wallet',
                    'query' => [
                        ['key' => 'guest_token', 'value' => '{{guest_token}}', 'description' => 'Optional'],
                    ],
                ],
                [
                    'name' => 'Transactions',
                    'method' => 'GET',
                    'path' => '/api/v1/gamification/transactions',
                    'query' => [
                        ['key' => 'limit', 'value' => '10', 'description' => '1–50, default 10'],
                    ],
                ],
                [
                    'name' => 'Dispatch Event',
                    'method' => 'POST',
                    'path' => '/api/v1/gamification/dispatch',
                    'body' => [
                        'event' => 'ghost_mode.daily_checkin',
                        'metadata' => [],
                        'guest_token' => '{{guest_token}}',
                    ],
                    'description' => 'See GamificationEvent enum for all event values.',
                ],
                [
                    'name' => 'Preview Event',
                    'method' => 'POST',
                    'path' => '/api/v1/gamification/preview',
                    'body' => ['event' => 'ghost_mode.daily_checkin', 'metadata' => []],
                ],
                [
                    'name' => 'Shop',
                    'method' => 'GET',
                    'path' => '/api/v1/gamification/shop',
                ],
                [
                    'name' => 'Purchase Shop Item',
                    'method' => 'POST',
                    'path' => '/api/v1/gamification/shop/{{shop_item_slug}}/purchase',
                    'query' => [
                        ['key' => 'guest_token', 'value' => '{{guest_token}}', 'description' => 'Optional'],
                    ],
                ],
                [
                    'name' => 'Consume Perk',
                    'method' => 'POST',
                    'path' => '/api/v1/gamification/perks/{{perk_slug}}/consume',
                    'query' => [
                        ['key' => 'guest_token', 'value' => '{{guest_token}}', 'description' => 'Optional'],
                    ],
                ],
            ],
            '10 — Aether Engine' => [
                [
                    'name' => 'List Programs',
                    'method' => 'GET',
                    'path' => '/api/v1/aether/programs',
                    'query' => [
                        ['key' => 'mission_enrollment_uuid', 'value' => '{{mission_enrollment_uuid}}', 'description' => 'Optional filter'],
                    ],
                ],
                [
                    'name' => 'Show Program',
                    'method' => 'GET',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}',
                ],
                [
                    'name' => 'Toggle Workout Set',
                    'method' => 'POST',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}/workout-days/{{workout_day_id}}/sets/{{exercise_set_id}}/toggle',
                ],
                [
                    'name' => 'Log Set Weight',
                    'method' => 'POST',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}/workout-days/{{workout_day_id}}/sets/{{exercise_set_id}}/weight',
                    'body' => ['weight_kg' => 60],
                ],
                [
                    'name' => 'Check-in Status',
                    'method' => 'GET',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}/check-in/status',
                ],
                [
                    'name' => 'Submit Check-in',
                    'method' => 'POST',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}/check-in',
                    'body' => [
                        'sessions_completed' => 3,
                        'intensity_rating' => 2,
                        'had_pain' => false,
                        'pain_notes' => '',
                    ],
                ],
                [
                    'name' => 'Volume Chart',
                    'method' => 'GET',
                    'path' => '/api/v1/aether/programs/{{aether_program_uuid}}/volume-chart',
                    'query' => [
                        ['key' => 'days', 'value' => '30', 'description' => '7–90, default 30'],
                    ],
                ],
            ],
            '11 — Virtue Engine' => [
                [
                    'name' => 'List Habits',
                    'method' => 'GET',
                    'path' => '/api/v1/virtue/habits',
                ],
                [
                    'name' => 'Analyze Habit',
                    'method' => 'POST',
                    'path' => '/api/v1/virtue/habits/analyze',
                    'body' => ['description' => 'Stop scrolling social media before bed'],
                ],
                [
                    'name' => 'List Routines',
                    'method' => 'GET',
                    'path' => '/api/v1/virtue/routines',
                    'query' => [
                        ['key' => 'status', 'value' => '{{virtue_routine_status}}', 'description' => 'Optional filter'],
                    ],
                ],
                [
                    'name' => 'Start Routine',
                    'method' => 'POST',
                    'path' => '/api/v1/virtue/routines',
                    'body' => [
                        'virtue_habit_id' => '{{virtue_habit_id}}',
                        'personal_note' => 'My commitment',
                        'goal_type' => 'days_count',
                        'goal_target' => 30,
                    ],
                    'test_script' => [
                        'if (pm.response.code === 201) {',
                        '    const id = pm.response.json().data?.id;',
                        "    if (id) { pm.environment.set('virtue_routine_id', String(id)); }",
                        '}',
                    ],
                ],
                [
                    'name' => 'Routine Progress',
                    'method' => 'GET',
                    'path' => '/api/v1/virtue/routines/{{virtue_routine_id}}',
                ],
                [
                    'name' => 'Log Success',
                    'method' => 'POST',
                    'path' => '/api/v1/virtue/routines/{{virtue_routine_id}}/success',
                    'body' => ['situation' => 'Resisted urge', 'emotional_state' => 'calm'],
                ],
                [
                    'name' => 'Log Slip',
                    'method' => 'POST',
                    'path' => '/api/v1/virtue/routines/{{virtue_routine_id}}/slip',
                    'body' => ['what_happened' => 'Gave in briefly', 'choose_punishment_id' => null],
                ],
                [
                    'name' => 'Complete Routine',
                    'method' => 'POST',
                    'path' => '/api/v1/virtue/routines/{{virtue_routine_id}}/complete',
                ],
            ],
            '12 — Community Engine' => [
                [
                    'name' => 'Feed',
                    'method' => 'GET',
                    'path' => '/api/v1/community/posts',
                    'auth' => 'none',
                    'query' => [
                        ['key' => 'sort', 'value' => 'latest', 'description' => 'latest | liked | discussed | mine'],
                        ['key' => 'per_page', 'value' => '15', 'description' => '5–30'],
                        ['key' => 'include_preview', 'value' => 'true', 'description' => 'boolean'],
                    ],
                    'description' => 'Optional bearer for personalization (optional.sanctum).',
                ],
                [
                    'name' => 'Show Post',
                    'method' => 'GET',
                    'path' => '/api/v1/community/posts/{{community_post_id}}',
                    'auth' => 'none',
                ],
                [
                    'name' => 'List Comments',
                    'method' => 'GET',
                    'path' => '/api/v1/community/posts/{{community_post_id}}/comments',
                    'auth' => 'none',
                    'query' => [
                        ['key' => 'limit', 'value' => '20', 'description' => '1–100'],
                        ['key' => 'offset', 'value' => '0', 'description' => '≥0'],
                    ],
                ],
                [
                    'name' => 'Create Post',
                    'method' => 'POST',
                    'path' => '/api/v1/community/posts',
                    'body' => ['content' => 'Sharing my journey today — feeling stronger.', 'is_anonymous' => false],
                    'test_script' => [
                        'if (pm.response.code === 201) {',
                        '    const id = pm.response.json().post?.id || pm.response.json().data?.id;',
                        "    if (id) { pm.environment.set('community_post_id', String(id)); }",
                        '}',
                    ],
                ],
                [
                    'name' => 'Delete Post',
                    'method' => 'DELETE',
                    'path' => '/api/v1/community/posts/{{community_post_id}}',
                ],
                [
                    'name' => 'React to Post',
                    'method' => 'POST',
                    'path' => '/api/v1/community/posts/{{community_post_id}}/react',
                    'body' => ['reaction_type' => 'like'],
                    'description' => 'ReactionType: like, love, fire, support, insight, strength, sad, hug, heartbreak',
                ],
                [
                    'name' => 'Create Comment',
                    'method' => 'POST',
                    'path' => '/api/v1/community/posts/{{community_post_id}}/comments',
                    'body' => ['content' => 'Great post!', 'is_anonymous' => false, 'parent_id' => null],
                    'test_script' => [
                        'if (pm.response.code === 201) {',
                        '    const id = pm.response.json().comment?.id || pm.response.json().data?.id;',
                        "    if (id) { pm.environment.set('community_comment_id', String(id)); }",
                        '}',
                    ],
                ],
                [
                    'name' => 'Delete Comment',
                    'method' => 'DELETE',
                    'path' => '/api/v1/community/comments/{{community_comment_id}}',
                ],
                [
                    'name' => 'React to Comment',
                    'method' => 'POST',
                    'path' => '/api/v1/community/comments/{{community_comment_id}}/react',
                    'body' => ['reaction_type' => 'support'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $variables
     * @return array<string, mixed>
     */
    private function buildEnvironment(string $name, array $variables): array
    {
        return [
            'id' => strtolower(str_replace([' ', '—'], ['-', ''], $name)).'-env',
            'name' => $name,
            'values' => array_map(
                fn (string $key, string $value): array => [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'default',
                    'enabled' => true,
                ],
                array_keys($variables),
                array_values($variables),
            ),
            '_postman_variable_scope' => 'environment',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function developmentVariables(): array
    {
        return [
            'base_url' => 'https://egomap.test',
            'cashier_path' => 'stripe',
            'accept_language' => 'en',
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'bearer_token' => '',
            'verification_token' => '',
            'verification_code' => '1234',
            'guest_token' => '',
            'quiz_slug' => 'mbti-personality',
            'quiz_session_uuid' => '',
            'tests_filter' => 'all',
            'plan_id' => '1',
            'stripe_checkout_session_id' => '',
            'mission_slug' => 'gym-bodybuilding',
            'mission_enrollment_uuid' => '',
            'report_date' => date('Y-m-d'),
            'aether_program_uuid' => '',
            'workout_day_id' => '1',
            'exercise_set_id' => '1',
            'shop_item_slug' => '',
            'perk_slug' => '',
            'virtue_habit_id' => '1',
            'virtue_routine_id' => '',
            'virtue_routine_status' => '',
            'community_post_id' => '1',
            'community_comment_id' => '1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function productionVariables(): array
    {
        return array_merge($this->developmentVariables(), [
            'base_url' => 'https://egomap.app',
            'user_email' => '',
            'user_password' => '',
        ]);
    }
}
