<x-mail::message>
# {{ __('auth.verification_email_heading') }}

{{ __('auth.verification_email_intro') }}

<x-mail::panel>
## {{ $code }}
</x-mail::panel>

{{ __('auth.verification_email_expires', ['minutes' => $expiresMinutes]) }}

{{ config('app.name') }}
</x-mail::message>
