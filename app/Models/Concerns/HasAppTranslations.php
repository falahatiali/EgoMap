<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Application-wide conventions for Spatie Translatable models.
 *
 * @see https://spatie.be/docs/laravel-translatable/v6/introduction
 */
trait HasAppTranslations
{
    use HasTranslations;

    public function useFallbackLocale(): bool
    {
        return true;
    }
}
