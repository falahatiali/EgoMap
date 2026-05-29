<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.mission_engine.edit_title'),
        'subtitle' => $template->slug,
        'backRoute' => 'admin.mission-engine.templates.index',
        'backLabel' => __('admin.mission_engine.back_to_list'),
    ])

    <div class="eg-admin-meta-row mb-3">
        <span class="eg-admin-tag">{{ __('admin.mission_engine.enrollments') }}: {{ number_format($enrollmentsCount) }}</span>
        <span class="eg-admin-tag">{{ __('admin.mission_engine.fields') }}: {{ number_format($fieldsCount) }}</span>
        <span class="eg-admin-tag">{{ __('admin.mission_engine.phases') }}: {{ number_format($phasesCount) }}</span>
    </div>

    <form wire:submit="save" class="eg-admin-form-grid">
        <div class="eg-admin-panel eg-admin-panel--padded">
            <h3 class="eg-admin-panel-title">{{ __('admin.mission_engine.details') }}</h3>

            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.slug') }}</span>
                <input type="text" class="eg-admin-input" wire:model="slug">
                @error('slug') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

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

            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.summary_en') }}</span>
                <textarea class="eg-admin-textarea" rows="3" wire:model="summaryEn"></textarea>
                @error('summaryEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.description_en') }}</span>
                <textarea class="eg-admin-textarea" rows="5" wire:model="descriptionEn"></textarea>
                @error('descriptionEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.mission_engine.category') }}</span>
                <select class="eg-admin-select" wire:model="categoryId">
                    <option value="">{{ __('admin.mission_engine.no_category') }}</option>
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
                    @error('difficulty') <span class="eg-admin-error">{{ $message }}</span> @enderror
                </label>
                <label class="eg-admin-field">
                    <span>{{ __('admin.mission_engine.estimated_days') }}</span>
                    <input type="number" min="1" class="eg-admin-input" wire:model="estimatedDays">
                    @error('estimatedDays') <span class="eg-admin-error">{{ $message }}</span> @enderror
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

            <label class="eg-admin-check">
                <input type="checkbox" wire:model="isFeatured">
                <span>{{ __('admin.mission_engine.is_featured') }}</span>
            </label>
        </div>

        <div class="eg-admin-panel eg-admin-panel--padded">
            <h3 class="eg-admin-panel-title">{{ __('admin.mission_engine.capabilities') }}</h3>
            <p class="eg-admin-page-sub">{{ __('admin.mission_engine.capabilities_help') }}</p>

            <div class="eg-admin-check-grid">
                @foreach ($capabilityTypes as $capability)
                    <label class="eg-admin-check" wire:key="cap-{{ $capability->id }}">
                        <input
                            type="checkbox"
                            value="{{ $capability->id }}"
                            wire:model="enabledCapabilityIds"
                        >
                        <span>
                            <i class="fa-solid {{ $capability->icon }}" aria-hidden="true"></i>
                            {{ $capability->getTranslation('name', 'en', true) }}
                            <small class="text-muted d-block">{{ $capability->key->value }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('enabledCapabilityIds') <span class="eg-admin-error">{{ $message }}</span> @enderror
        </div>

        <div class="eg-admin-form-actions">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('admin.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.actions.saving') }}</span>
            </button>
        </div>
    </form>
</div>
