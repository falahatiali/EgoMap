<?php

namespace App\Services\Quiz\RebootProtocol;

/**
 * Multi-layer recovery analysis: feature normalization, weighted dimensions,
 * fuzzy phase clustering, composite pattern rules, and dynamic prescriptions.
 */
class RebootProtocolAnalysisEngine
{
    public const VERSION = '2.1';

    /**
     * @param  array<string, int|list<int>>  $answers
     * @return array{
     *   version: string,
     *   features: array<string, float>,
     *   dimensions: array<string, float>,
     *   phase_memberships: array<string, float>,
     *   primary_phase: string,
     *   detected_patterns: list<array{key: string, en: string, fa: string, confidence: float}>,
     *   stability_score: int,
     *   urgency: float,
     *   emergency: bool,
     * }
     */
    public function analyze(array $answers): array
    {
        $features = $this->normalizeFeatures($answers);
        $dimensions = $this->computeDimensions($features);
        $memberships = $this->fuzzyPhaseMemberships($dimensions);
        $primaryPhase = $this->primaryPhaseKey($memberships);
        $patterns = $this->detectPatterns($dimensions, $features);
        $prescription = $this->dynamicPrescription($dimensions, $memberships, $features);
        $score = $this->computeRecoveryScore($dimensions, $features);

        return [
            'version' => self::VERSION,
            'features' => $features,
            'dimensions' => $dimensions,
            'phase_memberships' => $memberships,
            'primary_phase' => $primaryPhase,
            'detected_patterns' => $patterns,
            'stability_score' => $score,
            'urgency' => $dimensions['urgency'],
            'emergency' => $prescription['emergency'],
            'prescription' => $prescription,
        ];
    }

    /**
     * @param  array<string, int|list<int>>  $answers
     * @return array<string, float>
     */
    private function normalizeFeatures(array $answers): array
    {
        $situation = $this->scalar($answers['situation_type'] ?? 0);
        $timeSince = $this->scalar($answers['time_since_event'] ?? 0);
        $pain = $this->values($answers['pain_focus'] ?? []);
        $risks = $this->values($answers['immediate_risk'] ?? []);
        $contact = $this->scalar($answers['contact_after_breakup'] ?? 0);
        $reasons = $this->values($answers['breakup_reason'] ?? []);
        $timing = $this->scalar($answers['timing_feeling'] ?? 0);
        $relPattern = $this->scalar($answers['relationship_pattern'] ?? 0);
        $selfPatterns = $this->values($answers['self_pattern'] ?? []);
        $need = $this->scalar($answers['first_need'] ?? 0);

        $contactUrge = match (true) {
            $this->anyIn($risks, [1, 2]) => 1.0,
            $this->anyIn($risks, [3, 4]) => 0.85,
            $this->anyIn($risks, [6]) => 0.9,
            $this->anyIn($risks, [5]) => 0.5,
            default => 0.0,
        };

        return [
            'breakup_agency' => match ($situation) {
                2 => 1.0,
                3 => 0.85,
                1 => 0.55,
                6, 7 => 0.4,
                4 => 0.3,
                default => 0.2,
            },
            'time_recency' => $timeSince > 0 ? (7 - $timeSince) / 6 : 0.5,
            'on_off_dynamic' => in_array($situation, [7], true) || $contact === 6 ? 1.0 : 0.0,
            'missing_presence' => $this->flag($pain, 1),
            'rejection_feel' => $this->flag($pain, 2),
            'replaced_feel' => $this->flag($pain, 3),
            'guilt' => $this->flag($pain, 4),
            'betrayal_feel' => $this->flag($pain, 5),
            'emptiness' => $this->flag($pain, 6),
            'imagination' => $this->flag($pain, 7),
            'confusion' => $this->flag($pain, 8),
            'contact_urge' => $contactUrge,
            'sleep_appetite_loss' => $this->anyIn($risks, [5]) ? 1.0 : 0.0,
            'collapse_risk' => $this->anyIn($risks, [6]) ? 1.0 : 0.0,
            'stable_tonight' => $this->anyIn($risks, [7]) && ! $this->anyIn($risks, [1, 2, 3, 4, 5, 6]) ? 1.0 : 0.0,
            'contact_frequency' => match ($contact) {
                1 => 0.0,
                2 => 0.25,
                3 => 0.55,
                4 => 0.75,
                5 => 0.9,
                6 => 1.0,
                default => 0.5,
            },
            'begging_level' => $contact === 4 ? 1.0 : 0.0,
            'infidelity' => $this->flag($reasons, 6),
            'trust_damage' => $this->flag($reasons, 5),
            'emotional_distance_reason' => $this->flag($reasons, 3),
            'anxious_self_reason' => $this->flag($reasons, 8),
            'she_pulled_away' => $this->flag($reasons, 7),
            'stayed_too_long' => $timing === 3 ? 1.0 : 0.0,
            'too_soon' => $timing === 1 ? 1.0 : 0.0,
            'wasted_time' => $timing === 2 ? 1.0 : 0.0,
            'fix_belief' => $timing === 4 ? 1.0 : 0.0,
            'acceptance_block' => $timing === 5 ? 1.0 : 0.0,
            'relief_and_pain' => $timing === 6 ? 1.0 : 0.0,
            'chase_avoid' => $relPattern === 1 ? 1.0 : 0.0,
            'she_chase_then_pull' => $relPattern === 2 ? 1.0 : 0.0,
            'push_pull' => $relPattern === 3 ? 1.0 : 0.0,
            'overgive' => $relPattern === 4 ? 1.0 : 0.0,
            'attraction_no_safety' => $relPattern === 6 ? 1.0 : 0.0,
            'ignored_red_flags' => $relPattern === 7 ? 1.0 : 0.0,
            'anxious_on_distance' => $this->flag($selfPatterns, 1),
            'overthink' => $this->flag($selfPatterns, 2),
            'fast_attach' => $this->flag($selfPatterns, 3),
            'fix_people' => $this->flag($selfPatterns, 4),
            'ignore_red_flags_self' => $this->flag($selfPatterns, 5),
            'lose_self' => $this->flag($selfPatterns, 6),
            'cold_inside_suffer' => $this->flag($selfPatterns, 7),
            'chase_on_distance' => $this->flag($selfPatterns, 8),
            'need_stop_contact' => $need === 1 ? 1.0 : 0.0,
            'need_no_contact_protocol' => $need === 2 ? 1.0 : 0.0,
            'need_understanding' => $need === 3 ? 1.0 : 0.0,
            'need_stop_obsessing' => $need === 4 ? 1.0 : 0.0,
            'need_routine' => $need === 5 ? 1.0 : 0.0,
            'need_body_confidence' => $need === 6 ? 1.0 : 0.0,
            'need_move_on' => $need === 7 ? 1.0 : 0.0,
            'need_become_better' => $need === 8 ? 1.0 : 0.0,
        ];
    }

    /**
     * @param  list<int>  $values
     * @param  list<int>  $needles
     */
    private function anyIn(array $values, array $needles): bool
    {
        return array_intersect($values, $needles) !== [];
    }

    /**
     * @param  array<string, float>  $features
     * @return array<string, float>
     */
    private function computeDimensions(array $features): array
    {
        $anxiety = $this->clamp(
            $features['anxious_on_distance'] * 0.35
            + $features['overthink'] * 0.2
            + $features['chase_on_distance'] * 0.2
            + $features['imagination'] * 0.15
            + $features['contact_urge'] * 0.1,
        );

        $identityErosion = $this->clamp(
            $features['lose_self'] * 0.4
            + $features['stayed_too_long'] * 0.25
            + $features['overgive'] * 0.2
            + $features['emptiness'] * 0.15,
        );

        $dysregulation = $this->clamp(
            $features['collapse_risk'] * 0.45
            + $features['sleep_appetite_loss'] * 0.2
            + $features['rejection_feel'] * 0.1
            + $features['betrayal_feel'] * 0.1
            + $features['wasted_time'] * 0.1
            + $features['time_recency'] * 0.05,
        );

        $obsession = $this->clamp(
            $features['imagination'] * 0.3
            + $features['overthink'] * 0.3
            + $features['contact_urge'] * 0.2
            + $features['fix_belief'] * 0.2,
        );

        $readiness = $this->clamp(
            $features['need_routine'] * 0.2
            + $features['need_body_confidence'] * 0.2
            + $features['need_move_on'] * 0.15
            + $features['need_become_better'] * 0.3
            + ($features['acceptance_block'] > 0 ? 0.0 : 0.1)
            + ($features['fix_belief'] > 0 ? -0.15 : 0.0),
        );

        $urgency = $this->clamp(
            $features['contact_urge'] * 0.45
            + $features['collapse_risk'] * 0.35
            + $features['contact_frequency'] * 0.2,
        );

        $avoidantPartner = $this->clamp(
            $features['emotional_distance_reason'] * 0.25
            + $features['she_pulled_away'] * 0.25
            + $features['chase_avoid'] * 0.25
            + $features['attraction_no_safety'] * 0.15
            + $features['she_chase_then_pull'] * 0.1,
        );

        return [
            'anxiety' => $anxiety,
            'avoidant_partner' => $avoidantPartner,
            'identity_erosion' => $identityErosion,
            'dysregulation' => $dysregulation,
            'readiness' => $readiness,
            'obsession' => $obsession,
            'urgency' => $urgency,
        ];
    }

    /**
     * @param  array<string, float>  $dimensions
     * @return array<string, float>
     */
    private function fuzzyPhaseMemberships(array $dimensions): array
    {
        $prototypes = [
            'shock' => ['anxiety' => 0.8, 'identity_erosion' => 0.3, 'dysregulation' => 0.9, 'readiness' => 0.1, 'obsession' => 0.4, 'urgency' => 0.7],
            'withdrawal' => ['anxiety' => 0.7, 'identity_erosion' => 0.4, 'dysregulation' => 0.6, 'readiness' => 0.2, 'obsession' => 0.5, 'urgency' => 0.6],
            'instability' => ['anxiety' => 0.6, 'identity_erosion' => 0.4, 'dysregulation' => 0.5, 'readiness' => 0.3, 'obsession' => 0.6, 'urgency' => 0.7],
            'obsession_loop' => ['anxiety' => 0.9, 'identity_erosion' => 0.5, 'dysregulation' => 0.5, 'readiness' => 0.2, 'obsession' => 0.9, 'urgency' => 0.6],
            'identity_loss' => ['anxiety' => 0.6, 'identity_erosion' => 0.9, 'dysregulation' => 0.6, 'readiness' => 0.2, 'obsession' => 0.5, 'urgency' => 0.4],
            'stabilization' => ['anxiety' => 0.3, 'identity_erosion' => 0.3, 'dysregulation' => 0.3, 'readiness' => 0.6, 'obsession' => 0.3, 'urgency' => 0.2],
            'rebuild_ready' => ['anxiety' => 0.2, 'identity_erosion' => 0.2, 'dysregulation' => 0.2, 'readiness' => 0.9, 'obsession' => 0.2, 'urgency' => 0.1],
        ];

        $memberships = [];

        foreach ($prototypes as $phase => $proto) {
            $distance = 0.0;

            foreach ($proto as $dim => $target) {
                $distance += ($dimensions[$dim] - $target) ** 2;
            }

            $memberships[$phase] = 1 / (1 + sqrt($distance));
        }

        return $memberships;
    }

    /**
     * @param  array<string, float>  $memberships
     */
    private function primaryPhaseKey(array $memberships): string
    {
        $primary = 'stabilization';
        $best = 0.0;

        foreach ($memberships as $phase => $weight) {
            if ($weight > $best) {
                $best = $weight;
                $primary = $phase;
            }
        }

        return $primary;
    }

    /**
     * @param  array<string, float>  $dimensions
     * @param  array<string, float>  $features
     * @return list<array{key: string, en: string, fa: string, confidence: float}>
     */
    private function detectPatterns(array $dimensions, array $features): array
    {
        $candidates = [];

        $rules = [
            [
                'key' => 'anxious_avoidant',
                'en' => 'Anxious pursuit / avoidant distance',
                'fa' => 'دنبال کردن مضطربانه / فاصله اجتنابی',
                'score' => $dimensions['anxiety'] * 0.5 + $features['chase_avoid'] * 0.3 + $features['chase_on_distance'] * 0.2,
            ],
            [
                'key' => 'overgiving_identity',
                'en' => 'Overgiving & identity erosion',
                'fa' => 'بیش‌ازحد دادن و فرسایش هویت',
                'score' => $features['overgive'] * 0.4 + $dimensions['identity_erosion'] * 0.4 + $features['lose_self'] * 0.2,
            ],
            [
                'key' => 'push_pull',
                'en' => 'Push–pull / intermittent reinforcement',
                'fa' => 'هل‌دادن و کشیدن / تقویت متناوب',
                'score' => $features['push_pull'] * 0.35 + $features['on_off_dynamic'] * 0.35 + $features['attraction_no_safety'] * 0.2 + $dimensions['obsession'] * 0.1,
            ],
            [
                'key' => 'emotional_shutdown',
                'en' => 'Emotional numbness / shutdown',
                'fa' => 'بی‌حسی عاطفی / خاموش شدن',
                'score' => $features['cold_inside_suffer'] * 0.5 + $dimensions['dysregulation'] * 0.3 + $features['emptiness'] * 0.2,
            ],
            [
                'key' => 'trauma_bond',
                'en' => 'Possible trauma bond after betrayal',
                'fa' => 'پیوند تروماتیک محتمل پس از خیانت',
                'score' => $features['infidelity'] * 0.45 + $features['betrayal_feel'] * 0.35 + $features['push_pull'] * 0.2,
            ],
            [
                'key' => 'rumination_loop',
                'en' => 'Rumination & mental replay loop',
                'fa' => 'حلقه نشخوار و پخش ذهنی',
                'score' => $dimensions['obsession'] * 0.55 + $features['overthink'] * 0.25 + $features['imagination'] * 0.2,
            ],
        ];

        foreach ($rules as $rule) {
            if ($rule['score'] >= 0.55) {
                $candidates[] = [
                    'key' => $rule['key'],
                    'en' => $rule['en'],
                    'fa' => $rule['fa'],
                    'confidence' => round(min(1.0, $rule['score']), 2),
                ];
            }
        }

        usort($candidates, fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return array_slice($candidates, 0, 2);
    }

    /**
     * @param  array<string, float>  $dimensions
     * @param  array<string, float>  $memberships
     * @param  array<string, float>  $features
     * @return array{en: string, fa: string, emergency: bool}
     */
    private function dynamicPrescription(array $dimensions, array $memberships, array $features): array
    {
        if (($features['contact_urge'] >= 0.75 && $features['stable_tonight'] < 1.0) || $features['collapse_risk'] >= 1.0) {
            return [
                'en' => 'Open Emergency Mode now. Do not text or call. Write the message inside the app and wait 20 minutes.',
                'fa' => 'همین الان Emergency Mode را باز کن. پیام یا تماس نده. پیام را داخل اپ بنویس و ۲۰ دقیقه صبر کن.',
                'emergency' => true,
            ];
        }

        if ($dimensions['urgency'] > 0.65) {
            return [
                'en' => 'Open Emergency Mode now. Do not text or call. Write the message inside the app and wait 20 minutes.',
                'fa' => 'همین الان Emergency Mode را باز کن. پیام یا تماس نده. پیام را داخل اپ بنویس و ۲۰ دقیقه صبر کن.',
                'emergency' => true,
            ];
        }

        if ($dimensions['obsession'] > 0.7) {
            return [
                'en' => 'Start Fantasy Detox: write three ways the relationship hurt you — not the idealized version.',
                'fa' => 'Fantasy Detox: سه مورد بنویس که رابطه بهت آسیب زد — نه نسخه ایده‌آلش.',
                'emergency' => false,
            ];
        }

        if ($dimensions['identity_erosion'] > 0.6) {
            return [
                'en' => 'Identity Reset Mission: 30-minute walk alone + journal one truth about you that has nothing to do with her.',
                'fa' => 'ماموریت هویت: ۳۰ دقیقه پیاده‌روی تنها + یک حقیقت درباره خودت که ربطی به او نداشته باشد.',
                'emergency' => false,
            ];
        }

        if ($dimensions['readiness'] > 0.6) {
            return [
                'en' => 'Start 90-Day No Contact — Day zero. Use structure, not punishment.',
                'fa' => 'No Contact ۹۰ روزه — روز صفر. ساختار بساز، نه تنبیه.',
                'emergency' => false,
            ];
        }

        if ($dimensions['dysregulation'] > 0.6) {
            return [
                'en' => 'Stabilization first: no more analysis. Rest, hydrate, no big decisions for 24 hours.',
                'fa' => 'اول تثبیت: تحلیل نکن. استراحت، آب، ۲۴ ساعت بدون تصمیم بزرگ.',
                'emergency' => false,
            ];
        }

        if ($features['contact_frequency'] > 0.5) {
            return [
                'en' => 'Restart No Contact from Day Zero tonight — your nervous system needs distance.',
                'fa' => 'امشب No Contact را از روز صفر دوباره شروع کن — سیستم عصبی‌ات به فاصله نیاز دارد.',
                'emergency' => false,
            ];
        }

        return [
            'en' => 'Begin the No Contact protocol and check in tomorrow for your first daily mission.',
            'fa' => 'پروتکل No Contact را شروع کن و فردا برای اولین ماموریت روزانه برگرد.',
            'emergency' => false,
        ];
    }

    /**
     * @param  array<string, float>  $dimensions
     * @param  array<string, float>  $features
     */
    private function computeRecoveryScore(array $dimensions, array $features): int
    {
        $raw = (1 - $dimensions['anxiety']) * 20
            + (1 - $dimensions['identity_erosion']) * 20
            + (1 - $dimensions['dysregulation']) * 15
            + $dimensions['readiness'] * 30
            + (1 - $dimensions['obsession']) * 15;

        if ($features['contact_frequency'] < 0.2) {
            $raw += 10;
        }

        if ($features['time_recency'] > 0.8) {
            $raw -= 10;
        }

        if ($dimensions['urgency'] > 0.85) {
            $raw -= 8;
        }

        return (int) min(100, max(0, round($raw)));
    }

    /**
     * @return array{en: string, fa: string}
     */
    public function phaseLabel(string $phaseKey): array
    {
        return match ($phaseKey) {
            'shock' => ['en' => 'Shock Phase', 'fa' => 'مرحله شوک'],
            'withdrawal' => ['en' => 'Emotional Withdrawal', 'fa' => 'مرحله ترک عاطفی'],
            'instability' => ['en' => 'No-Contact Instability', 'fa' => 'بی‌ثباتی در No Contact'],
            'obsession_loop' => ['en' => 'Obsession Loop', 'fa' => 'حلقه وسواس فکری'],
            'identity_loss' => ['en' => 'Identity Loss', 'fa' => 'گم‌کردن هویت'],
            'rebuild_ready' => ['en' => 'Rebuild Ready', 'fa' => 'آماده بازسازی'],
            default => ['en' => 'Stabilization Ready', 'fa' => 'آماده تثبیت'],
        };
    }

    /**
     * @return array{en: string, fa: string}
     */
    public function phaseDescription(string $phaseKey): array
    {
        return match ($phaseKey) {
            'shock' => [
                'en' => 'Your system is still processing the impact. Structure matters more than answers right now.',
                'fa' => 'سیستم تو هنوز ضربه را هضم می‌کند. الان ساختار مهم‌تر از جواب است.',
            ],
            'withdrawal' => [
                'en' => 'Your brain is craving familiar attachment. This is withdrawal, not proof you must reach out.',
                'fa' => 'مغزت دلبستگی آشنا را می‌خواهد. این ترک است، نه اینکه حتماً باید پیام بدهی.',
            ],
            'instability' => [
                'en' => 'You are fighting the urge to reach out. No Contact is stabilization, not a tactic to win her back.',
                'fa' => 'با میل تماس مبارزه می‌کنی. No Contact تثبیت است، نه ترفند برگرداندن او.',
            ],
            'obsession_loop' => [
                'en' => 'Your mind is replaying the relationship on a loop. Action beats more analysis.',
                'fa' => 'ذهنت رابطه را پشت سر هم پخش می‌کند. اقدام از تحلیل بیشتر جلو می‌زند.',
            ],
            'identity_loss' => [
                'en' => 'You may have lost sight of who you are without her. Small identity wins come first.',
                'fa' => 'شاید فراموش کرده‌ای بدون او کی هستی. بردهای کوچک هویت اولویت دارند.',
            ],
            'rebuild_ready' => [
                'en' => 'You are leaning toward structure and rebuild. Channel that momentum into daily missions.',
                'fa' => 'به سمت ساختار و بازسازی مایلی. این انرژی را به ماموریت‌های روزانه بده.',
            ],
            default => [
                'en' => 'The storm is settling. Your next win is one structured day at a time.',
                'fa' => 'طوفان دارد آرام می‌شود. برد بعدی‌ات یک روز ساختاریافته است.',
            ],
        };
    }

    /**
     * @param  array<string, float>  $dimensions
     * @param  array<string, float>  $features
     * @return array{en: string, fa: string}
     */
    public function mainRiskFromAnalysis(array $dimensions, array $features): array
    {
        if ($dimensions['urgency'] > 0.75) {
            return [
                'en' => 'High risk of contacting her or emotional collapse tonight',
                'fa' => 'ریسک بالای تماس با او یا فروپاشی احساسی امشب',
            ];
        }

        if ($dimensions['obsession'] > 0.65) {
            return [
                'en' => 'Rumination and fantasy replay — your main battle is internal',
                'fa' => 'نشخوار و خیال‌پردازی — نبرد اصلی‌ات درون سر است',
            ];
        }

        if ($dimensions['identity_erosion'] > 0.6) {
            return [
                'en' => 'Identity erosion — you may feel empty without the relationship mirror',
                'fa' => 'فرسایش هویت — ممکن است بدون آینه رابطه احساس پوچی کنی',
            ];
        }

        return [
            'en' => 'Moderate internal risk — isolation or numbness may be building',
            'fa' => 'ریسک درونی متوسط — انزوا یا بی‌حسی ممکن است در حال شکل‌گیری باشد',
        ];
    }

    /**
     * @param  int|list<int>  $value
     */
    private function scalar(int|array $value): int
    {
        $values = $this->values($value);

        return $values[0] ?? 0;
    }

    /**
     * @return list<int>
     */
    private function values(int|array $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value), fn (int $item): bool => $item > 0));
        }

        return $value > 0 ? [(int) $value] : [];
    }

    /**
     * @param  list<int>  $haystack
     */
    private function flag(array $haystack, int $needle): float
    {
        return in_array($needle, $haystack, true) ? 1.0 : 0.0;
    }

    private function clamp(float $value): float
    {
        return min(1.0, max(0.0, $value));
    }
}
