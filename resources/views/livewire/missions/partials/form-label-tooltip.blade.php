@props(['label', 'help'])

<label {{ $attributes->merge(['class' => 'form-label eg-form-label-help d-inline-flex align-items-center gap-1 mb-1']) }}>
    <span>{{ $label }}</span>
    <button
        type="button"
        class="eg-field-help-btn"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-custom-class="eg-field-help-tooltip"
        title="{{ $help }}"
        aria-label="{{ __('missions.field_help') }}"
    >
        <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
    </button>
</label>
