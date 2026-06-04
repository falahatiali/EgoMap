<div class="row g-4">
    <div class="col-lg-5">
        <div class="eg-mission-block h-100">
            <h2 class="eg-mission-block-title">{{ __('missions.supplement_stack') }}</h2>
            <p class="eg-text-muted small">{{ __('missions.supplement_stack_help') }}</p>

            <ul class="list-unstyled eg-mission-product-list mb-4">
                @forelse ($supplementProducts as $product)
                    <li class="eg-mission-product-item">
                        <button type="button" class="btn btn-link text-start p-0 text-reset" wire:click="selectSupplementProduct({{ $product->id }})">
                            <strong>{{ $product->name }}</strong>
                            @if ($product->brand)<span class="eg-text-muted small"> — {{ $product->brand }}</span>@endif
                            <span class="d-block small eg-text-muted">{{ $product->default_amount }} {{ $product->default_unit }}</span>
                        </button>
                    </li>
                @empty
                    <li class="eg-text-muted small">{{ __('missions.no_supplements') }}</li>
                @endforelse
            </ul>

            <form wire:submit="addSupplementProduct" class="eg-mission-inline-form">
                <div class="mb-2">
                    <input type="text" class="form-control" wire:model="newSupplementName" placeholder="{{ __('missions.supplement_name') }}">
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control" wire:model="newSupplementBrand" placeholder="{{ __('missions.supplement_brand') }}">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <input type="text" class="form-control" wire:model="newSupplementDefaultAmount" placeholder="{{ __('missions.amount') }}">
                    </div>
                    <div class="col-6">
                        <input type="text" class="form-control" wire:model="newSupplementUnit" placeholder="{{ __('missions.unit') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-light btn-sm">{{ __('missions.add_to_stack') }}</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="eg-mission-block h-100">
            <h2 class="eg-mission-block-title">{{ __('missions.log_supplement_today') }}</h2>
            <form wire:submit="logSupplementIntake">
                <div class="mb-2">
                    <label class="form-label">{{ __('missions.supplement_name') }}</label>
                    <input type="text" class="form-control" wire:model="intakeProductName">
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('missions.supplement_brand') }}</label>
                    <input type="text" class="form-control" wire:model="intakeBrand">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label">{{ __('missions.amount') }}</label>
                        <input type="number" step="0.5" class="form-control" wire:model="intakeAmount">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('missions.unit') }}</label>
                        <input type="text" class="form-control" wire:model="intakeUnit">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('missions.intake_notes') }}</label>
                    <input type="text" class="form-control" wire:model="intakeNotes">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('missions.log_intake') }}</button>
            </form>

            @if ($supplementIntakes->isNotEmpty())
                <h3 class="h6 mt-4 mb-2">{{ __('missions.recent_intakes') }}</h3>
                <ul class="eg-mission-log-list">
                    @foreach ($supplementIntakes as $intake)
                        <li class="eg-mission-log-item">
                            <strong>{{ $intake->intake_date->translatedFormat('j M') }}</strong> —
                            {{ $intake->product_name }}
                            @if ($intake->brand) ({{ $intake->brand }}) @endif
                            · {{ eg_num($intake->amount) }} {{ $intake->unit }}
                        </li>
                    @endforeach
                </ul>
                {{ $supplementIntakes->links() }}
            @endif
        </div>
    </div>
</div>
