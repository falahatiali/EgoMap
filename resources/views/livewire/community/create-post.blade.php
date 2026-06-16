<div class="community-create-post">
    <form wire:submit="submit">

        <div class="community-create-post__field">
            <textarea wire:model.live="content" class="community-create-post__textarea"
                placeholder="{{ __('community.whats_on_your_mind') }}" rows="5" maxlength="1000"
                autofocus></textarea>
            @error('content')
                <p class="community-create-post__error">{{ $message }}</p>
            @enderror
            <div class="community-create-post__counter {{ strlen($content) > 900 ? 'community-create-post__counter--warn' : '' }}">
                {{ strlen($content) }} / 1000
            </div>
        </div>

        <div class="community-create-post__options">
            <label class="community-toggle-label">
                <input type="checkbox" wire:model="isAnonymous" class="community-toggle-check" />
                <span class="community-toggle-label__text">
                    <span class="community-toggle-label__icon">🎭</span>
                    {{ __('community.post_anonymously') }}
                </span>
            </label>
        </div>

        <div class="community-create-post__footer">
            <button type="submit" class="eg-btn eg-btn--primary community-submit-btn"
                wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">{{ __('community.share_with_community') }}</span>
                <span wire:loading wire:target="submit">{{ __('community.posting') }}</span>
            </button>
        </div>

    </form>
</div>
