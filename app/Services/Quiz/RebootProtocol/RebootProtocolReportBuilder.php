<?php

namespace App\Services\Quiz\RebootProtocol;

/**
 * Builds the Reboot Protocol report from the multi-layer analysis engine.
 */
class RebootProtocolReportBuilder
{
    public function __construct(
        private readonly RebootProtocolAnalysisEngine $engine,
    ) {}

    /**
     * @param  array<string, int|list<int>>  $answers
     * @return array<string, mixed>
     */
    public function build(array $answers): array
    {
        $analysis = $this->engine->analyze($answers);

        $phaseKey = $analysis['primary_phase'];
        $phase = $this->engine->phaseLabel($phaseKey);
        $phaseNarrative = $this->engine->phaseDescription($phaseKey);
        $prescription = $analysis['prescription'];
        $mainRisk = $this->engine->mainRiskFromAnalysis($analysis['dimensions'], $analysis['features']);
        $noContact = $this->noContactRecommendation($answers, $analysis['dimensions']);
        $timing = $this->timingReflection($answers);
        $relationship = $this->relationshipReflection($answers, $analysis['detected_patterns']);
        $self = $this->selfReflection($answers);
        $steps = $this->nextSteps($prescription, $phaseKey, $analysis['dimensions']);
        $score = $analysis['stability_score'];
        $phaseBlend = $this->phaseBlendNarrative($analysis['phase_memberships']);

        $disclaimer = [
            'en' => 'Your answers suggest a pattern, not a final diagnosis.',
            'fa' => 'جواب‌هایت یک الگو را نشان می‌دهند، نه یک تشخیص قطعی.',
        ];

        return [
            'template' => 'reboot_protocol',
            'type_code' => $this->phaseCode($phaseKey),
            'title' => $phase['en'],
            'summary' => $prescription['en'],
            'report_disclaimer' => $disclaimer,
            'stability_score' => $score,
            'phase' => $phase,
            'phase_narrative' => $phaseNarrative,
            'phase_blend' => $phaseBlend,
            'main_risk' => $mainRisk,
            'no_contact' => $noContact,
            'timing_reflection' => $timing,
            'relationship_reflection' => $relationship,
            'self_reflection' => $self,
            'first_prescription' => $prescription,
            'emergency' => $prescription['emergency'],
            'next_steps' => $steps,
            'detected_patterns' => $analysis['detected_patterns'],
            'phase_memberships' => $this->formattedMemberships($analysis['phase_memberships']),
            'analysis_version' => $analysis['version'],
            'dimensions' => $analysis['dimensions'],
            'content' => [
                'hero_label' => 'Your first map',
                'disclaimer_en' => $disclaimer['en'],
                'disclaimer_fa' => $disclaimer['fa'],
                'archetype' => $phase['en'],
                'tagline' => $this->scoreTagline($score)['en'],
                'narrative' => $phaseNarrative['en'],
                'sections' => $this->contentSections($mainRisk, $noContact, $relationship, $self, $analysis['detected_patterns'], $phaseBlend),
                'action_steps' => array_map(
                    fn (array $step, int $i) => [
                        'step' => $i + 1,
                        'en' => $step['en'],
                        'fa' => $step['fa'],
                    ],
                    $steps,
                    array_keys($steps),
                ),
            ],
        ];
    }

    private function phaseCode(string $phaseKey): string
    {
        return match ($phaseKey) {
            'shock' => 'shock',
            'withdrawal' => 'withdrawal',
            'instability' => 'withdrawal',
            'obsession_loop' => 'obsession',
            'identity_loss' => 'identity_loss',
            'rebuild_ready' => 'rebuild_ready',
            default => 'stabilization',
        };
    }

    /**
     * @param  array<string, float>  $memberships
     * @return array{en: string, fa: string}
     */
    private function phaseBlendNarrative(array $memberships): array
    {
        arsort($memberships);
        $top = array_slice($memberships, 0, 2, true);
        $partsEn = [];
        $partsFa = [];

        foreach ($top as $key => $weight) {
            if ($weight < 0.35) {
                continue;
            }

            $label = $this->engine->phaseLabel($key);
            $pct = (int) round($weight / array_sum($top) * 100);
            $partsEn[] = "{$pct}% {$label['en']}";
            $partsFa[] = "{$pct}٪ {$label['fa']}";
        }

        if ($partsEn === []) {
            return [
                'en' => 'Your recovery profile is mixed — we weighted several signals to find your best next step.',
                'fa' => 'پروفایل بازیابی تو ترکیبی است — چند سیگنال وزن‌دهی شد تا بهترین قدم بعدی پیدا شود.',
            ];
        }

        return [
            'en' => 'Profile blend: '.implode(' · ', $partsEn).'.',
            'fa' => 'ترکیب پروفایل: '.implode(' · ', $partsFa).'.',
        ];
    }

    /**
     * @param  array<string, float>  $memberships
     * @return list<array{phase: string, label_en: string, label_fa: string, weight: float, percent: int}>
     */
    private function formattedMemberships(array $memberships): array
    {
        $sum = array_sum($memberships) ?: 1;
        $rows = [];

        foreach ($memberships as $phase => $weight) {
            $label = $this->engine->phaseLabel($phase);
            $rows[] = [
                'phase' => $phase,
                'label_en' => $label['en'],
                'label_fa' => $label['fa'],
                'weight' => round($weight, 3),
                'percent' => (int) round(($weight / $sum) * 100),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['percent'] <=> $a['percent']);

        return $rows;
    }

    /**
     * @param  array{en: string, fa: string}  $mainRisk
     * @param  array{en: string, fa: string}  $noContact
     * @param  array{en: string, fa: string}  $relationship
     * @param  array{en: string, fa: string}  $self
     * @param  list<array{key: string, en: string, fa: string, confidence: float}>  $patterns
     * @param  array{en: string, fa: string}  $phaseBlend
     * @return list<array{heading_en: string, heading_fa: string, body_en: string, body_fa: string}>
     */
    private function contentSections(
        array $mainRisk,
        array $noContact,
        array $relationship,
        array $self,
        array $patterns,
        array $phaseBlend,
    ): array {
        $sections = [
            ['heading_en' => 'Profile blend', 'heading_fa' => 'ترکیب پروفایل', 'body_en' => $phaseBlend['en'], 'body_fa' => $phaseBlend['fa']],
            ['heading_en' => 'Main risk', 'heading_fa' => 'ریسک اصلی', 'body_en' => $mainRisk['en'], 'body_fa' => $mainRisk['fa']],
            ['heading_en' => 'No contact', 'heading_fa' => 'No Contact', 'body_en' => $noContact['en'], 'body_fa' => $noContact['fa']],
        ];

        if ($patterns !== []) {
            $patternEn = collect($patterns)->map(fn (array $p): string => "{$p['en']} ({$this->confidencePercent($p['confidence'])}%)")->implode('; ');
            $patternFa = collect($patterns)->map(fn (array $p): string => "{$p['fa']} ({$this->confidencePercent($p['confidence'])}٪)")->implode('؛ ');

            $sections[] = [
                'heading_en' => 'Detected patterns',
                'heading_fa' => 'الگوهای شناسایی‌شده',
                'body_en' => "Your answers suggest: {$patternEn}.",
                'body_fa' => "جواب‌های تو نشان می‌دهد: {$patternFa}.",
            ];
        }

        $sections[] = ['heading_en' => 'Relationship dynamic', 'heading_fa' => 'دینامیک رابطه', 'body_en' => $relationship['en'], 'body_fa' => $relationship['fa']];
        $sections[] = ['heading_en' => 'Your pattern', 'heading_fa' => 'الگوی تو', 'body_en' => $self['en'], 'body_fa' => $self['fa']];

        return $sections;
    }

    private function confidencePercent(float $confidence): int
    {
        return (int) round($confidence * 100);
    }

    /**
     * @param  array<string, int|list<int>>  $a
     * @param  array<string, float>  $dimensions
     * @return array{en: string, fa: string}
     */
    private function noContactRecommendation(array $a, array $dimensions): array
    {
        $contact = $this->scalar($a['contact_after_breakup'] ?? 0);
        $risks = $this->values($a['immediate_risk'] ?? []);

        if ($contact > 1 || array_intersect($risks, [1, 2, 3, 4]) !== [] || $dimensions['urgency'] > 0.55) {
            return [
                'en' => "Your first priority is not closure. It's distance. No contact is space for your nervous system to stabilize — not a trick to get her back.",
                'fa' => 'اولویت اول تو گرفتن جواب نهایی نیست. اولویت اول فاصله است. No Contact فضایی است برای آرام شدن سیستم عصبی‌ات — نه ترفند برگرداندن او.',
            ];
        }

        return [
            'en' => 'You seem ready to use no contact as structure, not punishment.',
            'fa' => 'به نظر می‌رسد آماده‌ای از No Contact به عنوان ساختار استفاده کنی، نه تنبیه.',
        ];
    }

    /**
     * @param  array<string, int|list<int>>  $a
     * @return array{en: string, fa: string}
     */
    private function timingReflection(array $a): array
    {
        $timing = $this->scalar($a['timing_feeling'] ?? 0);

        return match ($timing) {
            3 => [
                'en' => 'Your answers suggest you may have stayed longer than was healthy for your identity.',
                'fa' => 'جواب‌های تو نشون می‌ده ممکنه بیشتر از حد سالم، در رابطه مونده باشی و هویتت آسیب دیده باشه.',
            ],
            1 => [
                'en' => 'Your answers suggest the breakup may have happened before you were emotionally ready.',
                'fa' => 'جواب‌های تو می‌گه ممکنه جدایی قبل از آمادگی احساسی تو اتفاق افتاده باشه.',
            ],
            5 => [
                'en' => 'Your answers suggest the timing is still emotionally unresolved for you.',
                'fa' => 'به نظر می‌رسه زمان‌بندی این جدایی هنوز از نظر احساسی برای تو حل نشده‌ست.',
            ],
            2 => [
                'en' => 'Your answers suggest you feel you wasted time. That regret is a signal, not a sentence.',
                'fa' => 'جواب‌های تو می‌گه حس می‌کنی وقت تلف کردی. این پشیمانی یک سیگناله، نه حکم.',
            ],
            6 => [
                'en' => 'Your answers suggest you may feel both relief and grief. That mix is common — and valid.',
                'fa' => 'جواب‌های تو می‌گه ممکن است هم آرامش و هم غم را با هم حس کنی. این ترکیب رایج است — و معتبر.',
            ],
            default => [
                'en' => 'The timing of this breakup is still settling. Give it structure, not more analysis.',
                'fa' => 'زمان‌بندی این جدایی هنوز داره جا می‌افته. بهش ساختار بده، نه تحلیل بیشتر.',
            ],
        };
    }

    /**
     * @param  array<string, int|list<int>>  $a
     * @param  list<array{key: string, en: string, fa: string, confidence: float}>  $patterns
     * @return array{en: string, fa: string}
     */
    private function relationshipReflection(array $a, array $patterns): array
    {
        foreach ($patterns as $pattern) {
            if ($pattern['key'] === 'anxious_avoidant') {
                return [
                    'en' => 'Your answers point toward an anxious pursuit / avoidant distance dynamic.',
                    'fa' => 'جواب‌ها به دینامیک «دنبال کردن مضطربانه / فاصله اجتنابی» اشاره می‌کنند.',
                ];
            }

            if ($pattern['key'] === 'overgiving_identity') {
                return [
                    'en' => 'The pattern may be over-giving while slowly losing yourself.',
                    'fa' => 'الگو ممکن است «زیاد دادن» همراه با گم کردن تدریجی خودت باشد.',
                ];
            }

            if ($pattern['key'] === 'push_pull') {
                return [
                    'en' => 'There may have been an unstable push–pull cycle.',
                    'fa' => 'ممکن است یک چرخه بی‌ثبات هل‌دادن و کشیدن وجود داشته باشد.',
                ];
            }

            if ($pattern['key'] === 'trauma_bond') {
                return [
                    'en' => 'Betrayal pain is real. Closure will not come from decoding her — it comes from your rebuild.',
                    'fa' => 'درد خیانت واقعی است. بسته شدن از رمزگشایی او نمی‌آید — از بازسازی تو می‌آید.',
                ];
            }
        }

        $rel = $this->scalar($a['relationship_pattern'] ?? 0);

        return match ($rel) {
            1 => [
                'en' => 'Your answers point toward pursuit when she pulled away.',
                'fa' => 'جواب‌ها نشان می‌دهد وقتی او فاصله گرفت، تو دنبال کردی.',
            ],
            4 => [
                'en' => 'The pattern may be over-giving and under-receiving.',
                'fa' => 'الگو ممکن است «زیادی دادن و کم گرفتن» بوده باشد.',
            ],
            3 => [
                'en' => 'There may have been an on–off dynamic.',
                'fa' => 'ممکن است یک چرخه قطع و وصل وجود داشته باشد.',
            ],
            default => [
                'en' => 'The relationship dynamic is still emerging. Focus on your rebuild first.',
                'fa' => 'دینامیک رابطه هنوز روشن نیست. اول روی بازسازی خودت تمرکز کن.',
            ],
        };
    }

    /**
     * @param  array<string, int|list<int>>  $a
     * @return array{en: string, fa: string}
     */
    private function selfReflection(array $a): array
    {
        $patterns = $this->values($a['self_pattern'] ?? []);

        $snippets = [
            1 => ['en' => 'You seem to lose your center when she becomes distant.', 'fa' => 'وقتی او فاصله می‌گیرد، مرکز خودت را گم می‌کنی.'],
            2 => ['en' => 'You may confuse overthinking with problem-solving.', 'fa' => 'ممکن است بیش‌فکری را با حل مسئله اشتباه بگیری.'],
            3 => ['en' => 'You may attach to potential faster than reality.', 'fa' => 'ممکن است به پتانسیل سریع‌تر از واقعیت بچسبی.'],
            4 => ['en' => 'You may try to fix people instead of choosing whole partners.', 'fa' => 'ممکن است بخواهی آدم‌ها را درست کنی به جای انتخاب شریک درست.'],
            5 => ['en' => 'You may ignore red flags when you want someone badly.', 'fa' => 'وقتی کسی را خیلی می‌خواهی، ممکن است پرچم قرمزها را نادیده بگیری.'],
            6 => ['en' => 'You may lose routine, goals, or identity in relationships.', 'fa' => 'ممکن است در رابطه روتین، هدف، یا هویتت را گم کنی.'],
            7 => ['en' => 'You may act cold but suffer inside. Stabilization beats analysis.', 'fa' => 'ممکن است سرد باشی ولی درونت بسوزد. تثبیت از تحلیل مهم‌تر است.'],
            8 => ['en' => 'You may chase when you feel distance — a survival reflex, not love.', 'fa' => 'وقتی فاصله حس می‌کنی ممکن است بیشتر دنبال کنی — رفلکس بقا، نه عشق.'],
            9 => ['en' => 'Your pattern is still emerging. Start with action.', 'fa' => 'الگوی تو هنوز شکل می‌گیرد. با اقدام شروع کن.'],
        ];

        if ($patterns === []) {
            return [
                'en' => 'Self-awareness is already a win. You are on the right path.',
                'fa' => 'خودآگاهی خودش یک برد است. در مسیر درستی هستی.',
            ];
        }

        if (count($patterns) === 1) {
            return $snippets[$patterns[0]] ?? $snippets[9];
        }

        $first = $snippets[$patterns[0]] ?? $snippets[9];
        $second = $snippets[$patterns[1]] ?? null;

        if ($second === null) {
            return $first;
        }

        return [
            'en' => $first['en'].' You also recognize that '.$second['en'],
            'fa' => $first['fa'].' همچنین می‌بینی که '.$second['fa'],
        ];
    }

    /**
     * @param  array{en: string, fa: string, emergency: bool}  $prescription
     * @param  array<string, float>  $dimensions
     * @return list<array{en: string, fa: string}>
     */
    private function nextSteps(array $prescription, string $phaseKey, array $dimensions): array
    {
        $emergency = $prescription['emergency'];

        $step1 = $emergency
            ? ['en' => 'Open Emergency Mode and write the message. Do not send it.', 'fa' => 'Emergency Mode را باز کن و پیام را بنویس. نفرست.']
            : ['en' => $prescription['en'], 'fa' => $prescription['fa']];

        $step2 = $emergency
            ? ['en' => 'After the hold: one replacement activity (walk, shower, or pushups).', 'fa' => 'بعد از مکث: یک فعالیت جایگزین (پیاده‌روی، دوش، یا شنا).']
            : match ($phaseKey) {
                'obsession_loop' => ['en' => 'Next 24h: no profile checks + write one truth journal line.', 'fa' => '۲۴ ساعت بعدی: بدون چک پروفایل + یک خط حقیقت در ژورنال.'],
                'identity_loss' => ['en' => 'Next 24h: one walk alone + name one value that is yours.', 'fa' => '۲۴ ساعت بعدی: یک پیاده‌روی تنها + یک ارزش که مال خودت است.'],
                'rebuild_ready' => ['en' => 'Next 24h: No Contact Day 0 + one body task + schedule tomorrow.', 'fa' => '۲۴ ساعت بعدی: No Contact روز صفر + یک کار بدن + برنامه فردا.'],
                default => ['en' => 'Next 24h: No Contact Day 0 + one body task + one truth journal.', 'fa' => '۲۴ ساعت بعدی: No Contact روز صفر + یک کار بدن + یک ژورنال حقیقت.'],
            };

        $step3 = match (true) {
            $dimensions['dysregulation'] > 0.6 => [
                'en' => 'Next 7 days: sleep anchor + daily walk + no contact check-in each night.',
                'fa' => '۷ روز بعدی: لنگر خواب + پیاده‌روی روزانه + چک‌این No Contact هر شب.',
            ],
            $dimensions['obsession'] > 0.6 => [
                'en' => 'Next 7 days: fantasy detox list + one physical task + one truth journal daily.',
                'fa' => '۷ روز بعدی: لیست Fantasy Detox + یک کار بدنی + یک ژورنال حقیقت روزانه.',
            ],
            default => [
                'en' => 'Next 7 days: each day — one physical task, one no-contact reminder, one truth journal.',
                'fa' => '۷ روز بعدی: هر روز — یک کار فیزیکی، یک یادآور No Contact، یک خط حقیقت.',
            ],
        };

        return [$step1, $step2, $step3];
    }

    /**
     * @return array{en: string, fa: string}
     */
    private function scoreTagline(int $score): array
    {
        $band = match (true) {
            $score < 40 => [
                'en' => 'Your system needs immediate structure. This is not failure.',
                'fa' => 'سیستم تو به ساختار فوری نیاز دارد. این شکست نیست.',
            ],
            $score < 70 => [
                'en' => 'You are in the gray zone — consistency will move the needle.',
                'fa' => 'در منطقه خاکستری هستی — ثبات عقربه را جابه‌جا می‌کند.',
            ],
            default => [
                'en' => 'You are leaning toward rebuild — protect the momentum.',
                'fa' => 'به سمت بازسازی مایلی — این انرژی را حفظ کن.',
            ],
        };

        return [
            'en' => "Recovery Stability Score: {$score}%. {$band['en']}",
            'fa' => "امتیاز ثبات بازیابی: {$score}٪. {$band['fa']}",
        ];
    }

    /**
     * @param  int|list<int>  $value
     */
    private function scalar(int|array $value): int
    {
        if (is_array($value)) {
            $values = $this->values($value);

            return $values[0] ?? 0;
        }

        return $value > 0 ? (int) $value : 0;
    }

    /**
     * @param  int|list<int>  $value
     * @return list<int>
     */
    private function values(int|array $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value), fn (int $item): bool => $item > 0));
        }

        return $value > 0 ? [(int) $value] : [];
    }
}
