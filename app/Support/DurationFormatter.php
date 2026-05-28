<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * Human-readable duration strings with locale-aware digits and full unit words.
 */
final class DurationFormatter
{
    public static function formatDaysHoursMinutes(int $seconds, ?string $locale = null): string
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        $days = intdiv(max(0, $seconds), 86400);
        $hours = intdiv(max(0, $seconds) % 86400, 3600);
        $minutes = intdiv(max(0, $seconds) % 3600, 60);

        return Lang::get('no_contact.duration_full', [
            'days' => LocalizedNumbers::format($days, $locale),
            'hours' => LocalizedNumbers::format($hours, $locale),
            'minutes' => LocalizedNumbers::format($minutes, $locale),
        ], $locale);
    }

    /**
     * Pattern for client-side updates ({days}, {hours}, {minutes} placeholders).
     */
    public static function pattern(?string $locale = null): string
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        return Lang::get('no_contact.duration_full_pattern', [], $locale);
    }
}
