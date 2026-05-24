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

];
