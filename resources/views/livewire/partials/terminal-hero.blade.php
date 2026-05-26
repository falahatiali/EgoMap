{{-- Hard-reset terminal hook: black screen, typewriter, CTA only. --}}
<section
    class="eg-terminal"
    wire:transition:leave="eg-terminal-leave"
    wire:transition:leave.duration.400ms
    aria-label="{{ __('home.terminal_aria') }}"
>
    <div class="eg-terminal__scanlines" aria-hidden="true"></div>

    <div class="eg-terminal__stage">
        <p class="eg-terminal__line" aria-live="polite">
            <span
                class="eg-terminal__output"
                data-terminal-typewriter
                data-sequence="{{ json_encode($terminalSequence, JSON_UNESCAPED_UNICODE) }}"
            ></span><span class="eg-terminal__caret" aria-hidden="true">_</span>
        </p>

        <div class="eg-terminal__cta-block" data-terminal-cta hidden>
            <p class="eg-terminal__sub" data-i18n="home.terminal_subhead">{{ __('home.terminal_subhead') }}</p>

            <button
                type="button"
                class="eg-terminal__btn"
                wire:click="beginProtocol"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="beginProtocol" data-i18n="home.terminal_cta">{{ __('home.terminal_cta') }}</span>
                <span wire:loading wire:target="beginProtocol">…</span>
            </button>
        </div>
    </div>
</section>
