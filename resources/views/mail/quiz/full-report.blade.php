<x-mail::message>
# {{ $report['title'] ?? __('quiz.your_result') }}

**{{ $report['type_code'] ?? '' }}**

{{ $report['summary'] ?? '' }}

@if (! empty($report['dimensions']))
## {{ __('quiz.dimension_breakdown') }}

@foreach ($report['dimensions'] as $dimension)
- **{{ $dimension['left_label'] }} / {{ $dimension['right_label'] }}:** {{ $dimension['preference'] }}
@endforeach
@endif

<x-mail::button :url="$resultUrl">
{{ __('quiz.your_result') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
