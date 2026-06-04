<?php

namespace Modules\MissionEngine\Support;

use App\Support\LocaleConfig;
use Illuminate\Support\Facades\Lang;

final class MissionLocalizedText
{
    /**
     * @return array{en: string, fa: string}
     */
    public static function normalize(mixed $value): array
    {
        if (is_array($value)) {
            $en = trim((string) ($value['en'] ?? ''));
            $fa = trim((string) ($value['fa'] ?? ''));

            if ($en === '' && $fa !== '') {
                $en = self::translateLexicon($fa, 'en') ?? '';
            }

            if ($fa === '' && $en !== '') {
                $fa = self::translateLexicon($en, 'fa') ?? '';
            }

            return ['en' => $en, 'fa' => $fa];
        }

        if (! is_string($value)) {
            return ['en' => '', 'fa' => ''];
        }

        $text = trim($value);

        if ($text === '') {
            return ['en' => '', 'fa' => ''];
        }

        if (self::containsPersianScript($text)) {
            return [
                'en' => self::translateLexicon($text, 'en') ?? '',
                'fa' => $text,
            ];
        }

        return [
            'en' => $text,
            'fa' => self::translateLexicon($text, 'fa') ?? '',
        ];
    }

    public static function forLocale(mixed $value, ?string $locale = null): string
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $pair = self::normalize($value);

        return LocaleConfig::pick($pair, $locale);
    }

    /**
     * @return array{en: string, fa: string}
     */
    public static function merge(mixed $existing, string $newValue, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $pair = self::normalize($existing);
        $pair[$locale] = trim($newValue);

        if ($pair['en'] === '' && $pair['fa'] !== '' && $locale === 'en') {
            $pair['en'] = self::translateLexicon($pair['fa'], 'en') ?? '';
        }

        if ($pair['fa'] === '' && $pair['en'] !== '' && $locale === 'fa') {
            $pair['fa'] = self::translateLexicon($pair['en'], 'fa') ?? '';
        }

        return $pair;
    }

    public static function translateLexicon(string $text, string $toLocale): ?string
    {
        $needle = mb_strtolower(trim($text));

        if ($needle === '') {
            return null;
        }

        /** @var list<array{en: string, fa: string}> $lexicon */
        $lexicon = Lang::get('missions.body_parts_lexicon', [], 'en');

        if (! is_array($lexicon)) {
            return null;
        }

        foreach ($lexicon as $entry) {
            $en = mb_strtolower(trim((string) ($entry['en'] ?? '')));
            $fa = mb_strtolower(trim((string) ($entry['fa'] ?? '')));

            if ($toLocale === 'en' && ($needle === $fa || $needle === $en)) {
                return (string) $entry['en'];
            }

            if ($toLocale === 'fa' && ($needle === $en || $needle === $fa)) {
                return (string) $entry['fa'];
            }
        }

        return null;
    }

    private static function containsPersianScript(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }
}
