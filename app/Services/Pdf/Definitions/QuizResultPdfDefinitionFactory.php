<?php

namespace App\Services\Pdf\Definitions;

use App\DataTransferObjects\Pdf\PdfDocumentDefinition;
use App\DataTransferObjects\Pdf\PdfMailEnvelope;
use App\DataTransferObjects\Pdf\PdfMeta;
use App\DataTransferObjects\Pdf\PdfTheme;
use App\Enums\Pdf\PdfSectionType;
use App\Models\QuizSession;
use App\Support\LocaleConfig;
use App\Support\QuizResultViewData;
use Illuminate\Support\Str;

class QuizResultPdfDefinitionFactory
{
    /** @var array<string, array{0: string, 1: string}> */
    private const AXIS_COLORS = [
        'ei' => ['#8b5cf6', '#ede9fe'],
        'sn' => ['#0891b2', '#cffafe'],
        'tf' => ['#db2777', '#fce7f3'],
        'jp' => ['#d97706', '#fef3c7'],
    ];

    /** @var array<string, string> */
    private const GROUP_BACKGROUNDS = [
        'analyst' => '#f5f3ff',
        'diplomat' => '#ecfdf5',
        'sentinel' => '#eff6ff',
        'explorer' => '#fffbeb',
    ];

    /**
     * Build a generic PDF document definition from a completed quiz session.
     * Quiz-specific mapping lives here only — the PDF engine stays agnostic.
     */
    public static function fromSession(QuizSession $session, ?string $locale = null): PdfDocumentDefinition
    {
        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());
        $session->loadMissing(['result.outcomeProfile', 'quiz']);

        $resultData = QuizResultViewData::fromSession($session);
        $report = $resultData['report'];
        $content = $resultData['content'];
        $palette = $resultData['palette'];

        $typeCode = (string) ($report['type_code'] ?? '');
        $title = (string) ($report['title'] ?? $typeCode);
        $quizName = $session->quiz->getTranslation('name', $locale, true) ?? $session->quiz->slug;
        $tagline = (string) ($content['tagline'] ?? ($report['summary'] ?? ''));
        $summary = (string) ($report['summary'] ?? '');
        $groupKey = (string) ($palette['group'] ?? 'analyst');
        $groupLabel = __('pdf.group_'.$groupKey, locale: $locale);
        $questionCount = $session->quiz->questions()->where('is_active', true)->count();
        $completedLabel = $session->completed_at
            ? $session->completed_at->locale($locale)->translatedFormat('j M Y')
            : '—';

        $theme = new PdfTheme(
            accent: $palette['accent'],
            accentSoft: $palette['soft'],
            accentDark: self::darkenAccent($palette['accent']),
            background: self::GROUP_BACKGROUNDS[$groupKey] ?? '#f4f3ff',
            groupBackground: self::GROUP_BACKGROUNDS[$groupKey] ?? '#f8f7ff',
            groupLabel: $groupLabel,
        );

        $meta = new PdfMeta(
            title: $title,
            brand: (string) config('app.name'),
            subtitle: $tagline,
            footerNote: __('pdf.footer_note', ['brand' => config('app.name')], locale: $locale),
            actionUrl: route('quiz.result', ['uuid' => $session->uuid]),
            actionLabel: __('pdf.view_online', locale: $locale),
            generatedAtLabel: $session->completed_at
                ? __('pdf.generated_at', [
                    'date' => $session->completed_at->locale($locale)->translatedFormat('j F Y'),
                ], locale: $locale)
                : null,
        );

        $sections = [
            [
                'type' => PdfSectionType::Hero->value,
                'eyebrow' => $quizName,
                'ribbon' => __('pdf.report_badge', locale: $locale),
                'badge' => strtoupper($typeCode),
                'title' => $title,
                'subtitle' => $tagline,
                'meta' => $meta->generatedAtLabel,
                'group' => $groupLabel,
            ],
            [
                'type' => PdfSectionType::StatRow->value,
                'items' => [
                    [
                        'label' => __('pdf.stat_type', locale: $locale),
                        'value' => strtoupper($typeCode),
                        'tone' => 'primary',
                    ],
                    [
                        'label' => __('pdf.stat_group', locale: $locale),
                        'value' => $groupLabel,
                        'tone' => 'group',
                    ],
                    [
                        'label' => __('pdf.stat_questions', locale: $locale),
                        'value' => (string) $questionCount,
                        'tone' => 'neutral',
                    ],
                    [
                        'label' => __('pdf.stat_completed', locale: $locale),
                        'value' => $completedLabel,
                        'tone' => 'neutral',
                    ],
                ],
            ],
            [
                'type' => PdfSectionType::Overview->value,
                'title' => __('pdf.overview_title', locale: $locale),
                'body' => trim($summary) !== '' && $summary !== $tagline
                    ? $summary
                    : __('pdf.overview_intro', locale: $locale),
            ],
        ];

        if (! empty($report['dimensions'])) {
            $sections[] = [
                'type' => PdfSectionType::TypeLetters->value,
                'title' => __('pdf.type_letters_title', locale: $locale),
                'intro' => __('pdf.type_letters_intro', locale: $locale),
                'items' => collect($report['dimensions'])->map(function (array $dimension): array {
                    return [
                        'left' => (string) ($dimension['left_label'] ?? ''),
                        'right' => (string) ($dimension['right_label'] ?? ''),
                        'preference' => (string) ($dimension['preference'] ?? ''),
                        'key' => (string) ($dimension['key'] ?? ''),
                    ];
                })->all(),
            ];

            $sections[] = [
                'type' => PdfSectionType::DimensionBars->value,
                'title' => __('quiz.dimension_breakdown', locale: $locale),
                'intro' => __('pdf.dimensions_intro', locale: $locale),
                'items' => collect($report['dimensions'])->map(function (array $dimension) use ($locale): array {
                    $prefersRight = ($dimension['preference'] ?? '') === ($dimension['right_label'] ?? '');
                    $leftPercent = (int) ($dimension['percent'] ?? 50);
                    $winPercent = $prefersRight ? (100 - $leftPercent) : $leftPercent;
                    $key = (string) ($dimension['key'] ?? 'ei');
                    [$color, $soft] = self::AXIS_COLORS[$key] ?? ['#6366f1', '#eef2ff'];

                    return [
                        'left' => (string) ($dimension['left_label'] ?? ''),
                        'right' => (string) ($dimension['right_label'] ?? ''),
                        'preference' => (string) ($dimension['preference'] ?? ''),
                        'percent' => $winPercent,
                        'prefers_right' => $prefersRight,
                        'label' => __('pdf.axis_'.$key, locale: $locale),
                        'color' => $color,
                        'soft' => $soft,
                    ];
                })->all(),
            ];
        }

        if (! empty($content['strengths'])) {
            $sections[] = [
                'type' => PdfSectionType::ChipGrid->value,
                'title' => __('quiz.strengths_title', locale: $locale),
                'intro' => __('pdf.strengths_intro', locale: $locale),
                'tone' => 'success',
                'items' => array_values($content['strengths']),
            ];
        }

        if (! empty($content['growth_areas'])) {
            $sections[] = [
                'type' => PdfSectionType::NoteGrid->value,
                'title' => __('quiz.growth_title', locale: $locale),
                'intro' => __('pdf.growth_intro', locale: $locale),
                'tone' => 'warm',
                'items' => array_values($content['growth_areas']),
            ];
        }

        if (! empty($content['work_style'])) {
            $sections[] = [
                'type' => PdfSectionType::HighlightCard->value,
                'title' => __('quiz.work_style_title', locale: $locale),
                'body' => (string) $content['work_style'],
                'icon' => 'briefcase',
                'tone' => 'blue',
            ];
        }

        if (! empty($content['relationships'])) {
            $sections[] = [
                'type' => PdfSectionType::HighlightCard->value,
                'title' => __('quiz.relationships_title', locale: $locale),
                'body' => (string) $content['relationships'],
                'icon' => 'heart',
                'tone' => 'rose',
            ];
        }

        if (! empty($content['famous_examples'])) {
            $sections[] = [
                'type' => PdfSectionType::PillList->value,
                'title' => __('quiz.famous_title', locale: $locale),
                'intro' => __('pdf.famous_intro', locale: $locale),
                'items' => array_values($content['famous_examples']),
            ];
        }

        $sections[] = [
            'type' => PdfSectionType::Callout->value,
            'title' => __('pdf.next_steps_title', locale: $locale),
            'body' => __('pdf.next_steps_body', locale: $locale),
            'style' => 'tips',
        ];

        if ($meta->actionUrl !== null && $meta->actionLabel !== null) {
            $sections[] = [
                'type' => PdfSectionType::Callout->value,
                'title' => __('pdf.online_result_title', locale: $locale),
                'body' => __('pdf.online_result_body', locale: $locale),
                'url' => $meta->actionUrl,
                'label' => $meta->actionLabel,
                'style' => 'action',
            ];
        }

        $filename = Str::slug($typeCode.'-'.config('app.name').'-report').'.pdf';

        return new PdfDocumentDefinition(
            locale: $locale,
            filename: $filename,
            theme: $theme,
            meta: $meta,
            sections: $sections,
        );
    }

    public static function mailEnvelopeForSession(QuizSession $session, ?string $locale = null): PdfMailEnvelope
    {
        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());
        $session->loadMissing('result');

        $title = (string) ($session->result?->free_report['title'] ?? __('quiz.your_result', locale: $locale));

        return new PdfMailEnvelope(
            subject: __('quiz.email_subject', ['title' => $title], locale: $locale),
            headline: __('pdf.mail_headline', ['title' => $title], locale: $locale),
            intro: __('pdf.mail_intro', locale: $locale),
            actionUrl: route('quiz.result', ['uuid' => $session->uuid]),
            actionLabel: __('pdf.view_online', locale: $locale),
        );
    }

    private static function darkenAccent(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#4f46e5';
        }

        $parts = str_split($hex, 2);

        $darkened = array_map(function (string $part): string {
            $value = max(0, (int) round(hexdec($part) * 0.82));

            return str_pad(dechex($value), 2, '0', STR_PAD_LEFT);
        }, $parts);

        return '#'.implode('', $darkened);
    }
}
