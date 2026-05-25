<x-mail::message>
# {{ $mailEnvelope->headline }}

{{ $mailEnvelope->intro }}

{{ __('pdf.attachment_note', locale: $document->locale) }}

@if ($mailEnvelope->actionUrl && $mailEnvelope->actionLabel)
<x-mail::button :url="$mailEnvelope->actionUrl">
{{ $mailEnvelope->actionLabel }}
</x-mail::button>
@endif

{{ $document->meta->brand }}
</x-mail::message>
