<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class TranslationBundle
{
    /**
     * @param  list<string>  $groups  Lang file names without locale prefix (e.g. home, nav)
     * @return array{en: array<string, string>, fa: array<string, string>}
     */
    public static function forGroups(array $groups): array
    {
        $bundle = ['en' => [], 'fa' => []];

        foreach (['en', 'fa'] as $locale) {
            foreach ($groups as $group) {
                /** @var array<string, string> $lines */
                $lines = Lang::get($group, [], $locale);

                foreach ($lines as $key => $value) {
                    if (is_string($value)) {
                        $bundle[$locale]["{$group}.{$key}"] = $value;
                    }
                }
            }
        }

        return $bundle;
    }
}
