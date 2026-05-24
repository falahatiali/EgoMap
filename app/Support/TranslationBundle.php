<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class TranslationBundle
{
    /**
     * Static UI strings from lang files (not database content).
     *
     * @param  list<string>  $groups  Lang file names without locale prefix (e.g. home, nav)
     * @return array<string, array<string, string>>
     */
    public static function forGroups(array $groups): array
    {
        $bundle = [];

        foreach (LocaleConfig::supported() as $locale) {
            $bundle[$locale] = [];

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
