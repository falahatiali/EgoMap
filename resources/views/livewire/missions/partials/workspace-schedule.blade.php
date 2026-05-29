<form wire:submit="saveSchedule">
    <p class="eg-text-muted small">{{ __('missions.gym_days_help') }}</p>
    <div class="eg-mission-day-chips mb-3">
        @foreach ($dayOptions as $option)
            <label class="eg-mission-day-chip">
                <input type="checkbox" wire:model="gymDays" value="{{ $option['value'] }}">
                <span>{{ $option['label'] }}</span>
            </label>
        @endforeach
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('missions.preferred_time') }}</label>
        <input type="time" class="form-control" wire:model="preferredGymTime">
    </div>
    <button type="submit" class="btn btn-primary">{{ __('missions.save') }}</button>
</form>
