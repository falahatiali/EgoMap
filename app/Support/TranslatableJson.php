<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

final class TranslatableJson
{
    /**
     * Build a locale-keyed JSON payload for client-side i18n (data-i18n).
     *
     * @param  list<string>  $attributes  Translatable attribute names
     * @param  list<string>|null  $locales
     * @return array<string, array<string, string>>
     */
    public static function forModel(Model $model, array $attributes, ?array $locales = null): array
    {
        if (! in_array(HasTranslations::class, class_uses_recursive($model), true)) {
            return [];
        }

        $locales ??= LocaleConfig::supported();
        $prefix = "{$model->getTable()}.{$model->getKey()}";
        $bundle = array_fill_keys($locales, []);

        foreach ($locales as $locale) {
            foreach ($attributes as $attribute) {
                $bundle[$locale]["{$prefix}.{$attribute}"] = (string) $model->getTranslation($attribute, $locale, useFallbackLocale: true);
            }
        }

        return $bundle;
    }

    /**
     * Merge multiple model bundles into one payload.
     *
     * @param  array<string, array<string, string>>  $base
     * @param  array<string, array<string, string>>  $extra
     * @return array<string, array<string, string>>
     */
    public static function merge(array $base, array $extra): array
    {
        foreach ($extra as $locale => $strings) {
            $base[$locale] = array_merge($base[$locale] ?? [], $strings);
        }

        return $base;
    }
}
