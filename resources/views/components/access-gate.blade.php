@if ($allowed())
    {{ $slot }}
@elseif (isset($denied))
    {{ $denied }}
@endif
