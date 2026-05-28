<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.quizzes.edit_title'),
        'subtitle' => $quiz->slug,
        'backRoute' => 'admin.quizzes.index',
        'backLabel' => __('admin.quizzes.back_to_list'),
    ])

    <div class="eg-admin-meta-row mb-3">
        <span class="eg-admin-tag">{{ __('admin.quizzes.questions') }}: {{ number_format($questionsCount) }}</span>
        <span class="eg-admin-tag">{{ __('admin.quizzes.sessions') }}: {{ number_format($sessionsCount) }}</span>
    </div>

    <form wire:submit="save" class="eg-admin-form-grid">
        <div class="eg-admin-panel eg-admin-panel--padded">
            <h3 class="eg-admin-panel-title">{{ __('admin.quizzes.details') }}</h3>

            <label class="eg-admin-field">
                <span>{{ __('admin.quizzes.slug') }}</span>
                <input type="text" class="eg-admin-input" wire:model="slug">
                @error('slug') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.quizzes.type') }}</span>
                <select class="eg-admin-select" wire:model="type">
                    @foreach ($typeOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->value }}</option>
                    @endforeach
                </select>
                @error('type') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.quizzes.name_en') }}</span>
                <input type="text" class="eg-admin-input" wire:model="nameEn">
                @error('nameEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.quizzes.description_en') }}</span>
                <textarea class="eg-admin-textarea" rows="4" wire:model="descriptionEn"></textarea>
                @error('descriptionEn') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <div class="eg-admin-field-row">
                <label class="eg-admin-field">
                    <span>{{ __('admin.quizzes.estimated_minutes') }}</span>
                    <input type="number" min="1" class="eg-admin-input" wire:model="estimatedMinutes">
                    @error('estimatedMinutes') <span class="eg-admin-error">{{ $message }}</span> @enderror
                </label>
                <label class="eg-admin-field">
                    <span>{{ __('admin.quizzes.version') }}</span>
                    <input type="number" min="1" class="eg-admin-input" wire:model="version">
                    @error('version') <span class="eg-admin-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="eg-admin-check">
                <input type="checkbox" wire:model="isActive">
                <span>{{ __('admin.quizzes.is_active') }}</span>
            </label>
        </div>

        <div class="eg-admin-form-actions">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('admin.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.actions.saving') }}</span>
            </button>
        </div>
    </form>
</div>
