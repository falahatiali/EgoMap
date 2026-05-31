<form wire:submit="saveDetails" class="eg-admin-form-grid">
    @if ($errors->any())
        <div class="eg-admin-notice eg-admin-notice--danger mb-0" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ __('admin.mission_engine.form_has_errors') }}</span>
        </div>
    @endif

    <div class="eg-admin-panel eg-admin-panel--padded">
        @include('livewire.admin.mission-engine.templates.partials.section-lead', [
            'title' => __('admin.mission_engine.details'),
            'description' => __('admin.mission_engine.details_intro'),
            'icon' => 'fa-file-lines',
        ])

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.slug') }}</span>
            <input type="text" class="eg-admin-input eg-admin-input--mono" wire:model="slug">
            @error('slug') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>

        <div class="eg-admin-field-row">
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.title_en') }}</span>
                <input type="text" class="eg-admin-input" wire:model="titleEn">
                @error('titleEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.title_fa') }}</span>
                <input type="text" class="eg-admin-input" wire:model="titleFa" dir="rtl">
                @error('titleFa') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.summary_en') }}</span>
            <textarea class="eg-admin-textarea" rows="2" wire:model="summaryEn"></textarea>
            @error('summaryEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.summary_fa') }}</span>
            <textarea class="eg-admin-textarea" rows="2" wire:model="summaryFa" dir="rtl"></textarea>
            @error('summaryFa') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.description_en') }}</span>
            <textarea class="eg-admin-textarea" rows="4" wire:model="descriptionEn"></textarea>
            @error('descriptionEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.description_fa') }}</span>
            <textarea class="eg-admin-textarea" rows="4" wire:model="descriptionFa" dir="rtl"></textarea>
            @error('descriptionFa') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="eg-admin-panel eg-admin-panel--padded">
        @include('livewire.admin.mission-engine.templates.partials.section-lead', [
            'title' => __('admin.mission_engine.catalog_settings'),
            'description' => __('admin.mission_engine.catalog_intro'),
            'icon' => 'fa-store',
        ])

        <label class="eg-admin-field">
            <span>{{ __('admin.mission_engine.category') }}</span>
                <select class="eg-admin-select" wire:model="categoryId">
                    <option value="" @selected($categoryId === null)>{{ __('admin.mission_engine.no_category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->getTranslation('name', 'en', true) }}</option>
                @endforeach
            </select>
            @error('categoryId') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </label>

        <div class="eg-admin-field-row">
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.difficulty') }}</span>
                <select class="eg-admin-select" wire:model="difficulty">
                    @foreach ($difficultyOptions as $option)
                        <option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.estimated_days') }}</span>
                <input type="number" min="1" class="eg-admin-input" wire:model="estimatedDays">
            </label>
            <label class="eg-admin-field">
                <span>{{ __('admin.table.status') }}</span>
                <select class="eg-admin-select" wire:model="status">
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>
                    @endforeach
                </select>
                @error('status') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="eg-admin-field-row">
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.icon') }}</span>
                <input type="text" class="eg-admin-input eg-admin-input--mono" wire:model="icon" placeholder="fa-dumbbell">
                <small class="eg-admin-hint">{{ __('admin.mission_engine.icon_help') }}</small>
            </label>
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.sort_order') }}</span>
                <input type="number" min="0" class="eg-admin-input" wire:model="sortOrder">
            </label>
            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.accent') }}</span>
                <select class="eg-admin-select" wire:model="accent">
                    @foreach (['emerald', 'amber', 'indigo', 'rose', 'cyan'] as $accentOption)
                        <option value="{{ $accentOption }}">{{ str($accentOption)->headline() }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <label class="eg-admin-check">
            <input type="checkbox" wire:model="isFeatured">
            <span>{{ __('admin.mission_engine.is_featured') }}</span>
        </label>

        <label class="eg-admin-check">
            <input type="checkbox" wire:model="ghostModeRecommended">
            <span>{{ __('admin.mission_engine.ghost_recommended') }}</span>
        </label>
    </div>

    <div class="eg-admin-form-actions">
        <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="saveDetails">{{ __('admin.actions.save') }}</span>
            <span wire:loading wire:target="saveDetails">{{ __('admin.actions.saving') }}</span>
        </button>
    </div>
</form>
