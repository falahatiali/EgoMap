<div class="eg-mission-block">
    <h2 class="eg-mission-block-title">{{ __('missions.daily_report_full') }}</h2>
    <p class="eg-text-muted small mb-3">{{ __('missions.daily_report_full_help') }}</p>

    <form wire:submit="saveDailyReport">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('missions.daily_weight') }}</label>
                <input type="number" step="0.1" class="form-control" wire:model="reportWeight">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('missions.mood') }} {{ __('missions.scale_1_10') }}</label>
                <input type="number" min="1" max="10" class="form-control" wire:model="reportMood">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('missions.energy') }} (1–10)</label>
                <input type="number" min="1" max="10" class="form-control" wire:model="reportEnergy">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('missions.sleep_hours') }}</label>
                <input type="number" step="0.5" class="form-control" wire:model="reportSleep">
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mb-3">
            <label class="form-check">
                <input type="checkbox" class="form-check-input" wire:model="reportTrained">
                <span class="form-check-label">{{ __('missions.trained_today') }}</span>
            </label>
            <label class="form-check">
                <input type="checkbox" class="form-check-input" wire:model="reportNutritionLogged">
                <span class="form-check-label">{{ __('missions.nutrition_logged_today') }}</span>
            </label>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('missions.highlights') }}</label>
            <textarea class="form-control" rows="2" wire:model="reportHighlights"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('missions.challenges') }}</label>
            <textarea class="form-control" rows="2" wire:model="reportChallenges"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('missions.daily_notes') }}</label>
            <textarea class="form-control" rows="3" wire:model="reportNotes"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('missions.save_daily_report') }}</button>
    </form>
</div>

@if ($dailyReports->isNotEmpty())
    <div class="eg-mission-block mt-4">
        <h3 class="h6 mb-3">{{ __('missions.daily_history') }}</h3>
        @foreach ($dailyReports as $report)
            <article class="eg-mission-history-card mb-2">
                <strong>{{ $report->report_date->translatedFormat('j F Y') }}</strong>
                @if ($report->body_weight)<span class="ms-2">{{ eg_num($report->body_weight) }} {{ __('missions.weight_kg') }}</span>@endif
                @if ($report->highlights)<p class="small mb-0 mt-1">{{ $report->highlights }}</p>@endif
            </article>
        @endforeach
        {{ $dailyReports->links() }}
    </div>
@endif
