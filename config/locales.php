<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Application Locales
    |--------------------------------------------------------------------------
    |
    | Single source of truth for UI (lang files) and database content
    | (Spatie Translatable). Add a locale here first, then lang files + seeds.
    |
    */

    'supported' => ['en', 'fa'],

    'default' => env('APP_LOCALE', 'en'),

    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Right-to-Left Locales
    |--------------------------------------------------------------------------
    */

    'rtl' => ['fa'],

    /*
    |--------------------------------------------------------------------------
    | Display labels (native names for language switcher)
    |--------------------------------------------------------------------------
    |
    | Add an entry when you add a locale to "supported".
    |
    */

    'labels' => [
        'en' => 'English',
        'fa' => 'فارسی',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI prompt language names
    |--------------------------------------------------------------------------
    |
    | Human-readable language names sent to LLM instructions. Add one entry
    | per supported locale when you expand languages.
    |
    */

    'ai_language_names' => [
        'en' => 'English',
        'fa' => 'Persian (Farsi)',
    ],

    'short_labels' => [
        'en' => 'EN',
        'fa' => 'FA',
    ],

];
