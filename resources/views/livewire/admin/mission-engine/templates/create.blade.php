<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.mission_engine.create_title'),
        'subtitle' => __('admin.mission_engine.create_subtitle'),
        'backRoute' => 'admin.mission-engine.templates.index',
        'backLabel' => __('admin.mission_engine.back_to_list'),
    ])

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
                <span>{{ __('admin.mission_engine.summary_en') }}</span>
                <textarea class="eg-admin-textarea" rows="3" wire:model="summaryEn"></textarea>
                @error('summaryEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
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
            </div>
        </div>

        <div class="eg-admin-form-actions">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('admin.mission_engine.create') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.actions.saving') }}</span>
            </button>
        </div>
    </form>
</div>
