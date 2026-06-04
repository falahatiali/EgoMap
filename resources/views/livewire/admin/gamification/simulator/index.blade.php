<div>
    @include('partials.admin.page-head', ['title' => __('admin.gamification.simulator_title'), 'subtitle' => __('admin.gamification.simulator_subtitle'), 'backRoute' => null])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <form wire:submit="runSimulation" class="eg-admin-card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.col_event') }}</label>
                <select wire:model="event" class="form-select eg-admin-input">@foreach ($events as $e)<option value="{{ $e->value }}">{{ $e->value }}</option>@endforeach</select>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admin.gamification.metadata_json') }}</label>
                <textarea wire:model="metadataJson" rows="6" class="form-control eg-admin-input font-monospace"></textarea>
                @error('metadataJson')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="eg-admin-btn eg-admin-btn--primary mt-3">{{ __('admin.gamification.run_simulation') }}</button>
    </form>

    @if ($results !== [])
        <div class="eg-admin-card p-4">
            <h2 class="h6">{{ __('admin.gamification.simulation_results') }}</h2>
            <ul class="mb-0">@foreach ($results as $rule)<li wire:key="sim-{{ $rule['key'] }}"><strong>{{ $rule['name'] }}</strong> ({{ $rule['type'] }}) — <code>{{ json_encode($rule['effects']) }}</code></li>@endforeach</ul>
        </div>
    @endif
</div>
