<?php

namespace App\Support;

final class MbtiContentCatalog
{
    /** @var array<string, string> */
    private const LETTER_TO_AXIS_KEY = [
        'E' => 'extraversion',
        'I' => 'introversion',
        'S' => 'sensing',
        'N' => 'intuition',
        'T' => 'thinking',
        'F' => 'feeling',
        'J' => 'judging',
        'P' => 'perceiving',
    ];

    /**
     * @return array<string, mixed>|null
     */
    /**
     * Build translatable outcome profile content for database seeding.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function translatableOutcomeContent(string $typeCode): array
    {
        $content = [];

        foreach (LocaleConfig::supported() as $locale) {
            $content[$locale] = self::buildContentForType($typeCode, $locale);
        }

        return $content;
    }

    public static function profile(string $typeCode, ?string $locale = null): ?array
    {
        $code = strtolower($typeCode);
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        /** @var array<string, mixed>|null $character */
        $character = config("mbti_characters.characters.{$code}");

        if (! is_array($character)) {
            return null;
        }

        /** @var array<string, mixed>|null $localized */
        $localized = $character[$locale] ?? $character[LocaleConfig::fallback()] ?? null;

        return is_array($localized) ? $localized : null;
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    public static function buildContentForType(string $typeCode, ?string $locale = null, array $fallback = []): array
    {
        $profile = self::profile($typeCode, $locale);

        if ($profile === null) {
            return $fallback;
        }

        $featuredPeople = self::featuredPeople($typeCode, $locale);

        /** @var list<string> $strengths */
        $strengths = array_values(array_unique(array_filter(
            is_array($profile['strengths'] ?? null) ? $profile['strengths'] : [],
            fn (mixed $item): bool => is_string($item) && trim($item) !== '',
        )));

        /** @var list<string> $growthAreas */
        $growthAreas = array_values(array_filter(
            is_array($profile['growth_areas'] ?? null) ? $profile['growth_areas'] : [],
            fn (mixed $item): bool => is_string($item) && trim($item) !== '',
        ));

        $content = array_merge($fallback, array_filter([
            'tagline' => $profile['tagline'] ?? null,
            'hero_label' => $profile['hero_label'] ?? null,
            'mantra' => $profile['mantra'] ?? null,
            'narrative' => $profile['narrative'] ?? null,
            'work_style' => $profile['work_style'] ?? null,
            'relationships' => $profile['relationships'] ?? null,
            'communication_style' => $profile['communication_style'] ?? null,
            'under_stress' => $profile['under_stress'] ?? null,
            'ideal_environment' => $profile['ideal_environment'] ?? null,
            'archetype' => $profile['archetype'] ?? null,
            'group' => $profile['group'] ?? null,
        ], fn (mixed $value): bool => is_string($value) && trim($value) !== ''));

        if ($strengths !== []) {
            $content['strengths'] = $strengths;
        }

        if ($growthAreas !== []) {
            $content['growth_areas'] = $growthAreas;
        }

        if ($featuredPeople !== []) {
            $content['featured_people'] = $featuredPeople;
            $content['famous_examples'] = array_map(
                fn (array $person): string => (string) ($person['name'] ?? ''),
                $featuredPeople,
            );
        }

        return $content;
    }

    /**
     * @return list<array{key: string, name: string, role: string, bio: string, era: string, match_score: int, image_key: string}>
     */
    public static function featuredPeople(string $typeCode, ?string $locale = null): array
    {
        $code = strtolower($typeCode);
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        /** @var array<string, mixed>|null $character */
        $character = config("mbti_characters.characters.{$code}");

        if (! is_array($character)) {
            return [];
        }

        /** @var list<string> $keys */
        $keys = is_array($character['featured_people'] ?? null) ? $character['featured_people'] : [];

        /** @var array<string, array<string, mixed>> $registry */
        $registry = config('mbti_characters.famous_people', []);

        $people = [];

        foreach ($keys as $key) {
            if (! is_string($key) || ! isset($registry[$key]) || ! is_array($registry[$key])) {
                continue;
            }

            $person = $registry[$key];

            $people[] = [
                'key' => $key,
                'name' => self::localizedString($person['name'] ?? [], $locale),
                'role' => self::localizedString($person['role'] ?? [], $locale),
                'bio' => self::localizedString($person['bio'] ?? [], $locale),
                'era' => self::localizedString($person['era'] ?? [], $locale),
                'match_score' => (int) ($person['match_by_type'][$code] ?? 0),
                'image_key' => (string) ($person['image_key'] ?? $key),
            ];
        }

        return $people;
    }

    /**
     * @param  list<array<string, mixed>>  $dimensions
     * @return list<array<string, mixed>>
     */
    public static function enrichDimensions(array $dimensions, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        /** @var array<string, array<string, mixed>> $axisLabels */
        $axisLabels = config('mbti_characters.axis_labels', []);

        return array_map(function (array $dimension) use ($axisLabels, $locale): array {
            $preference = strtoupper((string) ($dimension['preference'] ?? ''));
            $axisKey = self::LETTER_TO_AXIS_KEY[$preference] ?? null;

            if ($axisKey === null || ! isset($axisLabels[$axisKey])) {
                return $dimension;
            }

            $axis = $axisLabels[$axisKey];

            $dimension['axis_name'] = self::localizedString($axis, $locale);
            $dimension['axis_description'] = self::localizedString($axis['desc'] ?? [], $locale);

            $leftLetter = strtoupper((string) ($dimension['left_label'] ?? ''));
            $rightLetter = strtoupper((string) ($dimension['right_label'] ?? ''));

            $leftKey = self::LETTER_TO_AXIS_KEY[$leftLetter] ?? null;
            $rightKey = self::LETTER_TO_AXIS_KEY[$rightLetter] ?? null;

            if ($leftKey !== null && isset($axisLabels[$leftKey])) {
                $dimension['left_name'] = self::localizedString($axisLabels[$leftKey], $locale);
            }

            if ($rightKey !== null && isset($axisLabels[$rightKey])) {
                $dimension['right_name'] = self::localizedString($axisLabels[$rightKey], $locale);
            }

            return $dimension;
        }, $dimensions);
    }

    /**
     * @param  array<string, mixed>|string  $value
     */
    private static function localizedString(array|string $value, string $locale): string
    {
        if (is_string($value)) {
            return $value;
        }

        return (string) ($value[$locale] ?? $value[LocaleConfig::fallback()] ?? '');
    }
}
