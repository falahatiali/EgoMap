@php
    $regConfig = collect($capabilities)->firstWhere('key', 'registration')['config'] ?? [];
    $checklist = $regConfig['checklist'] ?? [];
@endphp
<div class="eg-mission-checklist">
    @foreach ($checklist as $item)
        @php
            $stepKey = $item['key'] ?? '';
            $stepLabel = $item['label'][$locale] ?? $item['label']['en'] ?? $stepKey;
        @endphp
        <label>
            <input type="checkbox" @checked($registrationProgress[$stepKey] ?? false) wire:click="toggleRegistrationStep('{{ $stepKey }}')">
            <span>{{ $stepLabel }}</span>
        </label>
    @endforeach
</div>
