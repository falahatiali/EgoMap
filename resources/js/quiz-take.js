let audioContext = null;

function getAudioContext() {
    if (! audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    return audioContext;
}

function playOscillatorTick(ctx) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.06);

    gain.gain.setValueAtTime(0.0001, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.08);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.09);
}

export function playTick() {
    const root = document.querySelector('.eg-quiz-immersive');

    if (! root || root.dataset.soundEnabled !== '1') {
        return;
    }

    try {
        const ctx = getAudioContext();

        if (ctx.state === 'suspended') {
            void ctx.resume().then(() => playOscillatorTick(ctx));

            return;
        }

        playOscillatorTick(ctx);
    } catch {
        // Audio not available
    }
}

function pulseOption(button) {
    if (button.classList.contains('is-active')) {
        return;
    }

    button.classList.add('is-selected');

    window.setTimeout(() => {
        button.classList.remove('is-selected');
    }, 180);
}

function bindOption(button) {
    button.addEventListener('click', () => {
        playTick();
        pulseOption(button);
    }, { capture: true });
}

function bindSoundToggle() {
    document.querySelectorAll('.eg-quiz-sound-btn').forEach((btn) => {
        if (btn.dataset.bound === '1') {
            return;
        }

        btn.dataset.bound = '1';
        btn.addEventListener('click', () => {
            playTick();
        });
    });
}

function bindKeyboard() {
    document.addEventListener('keydown', (event) => {
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
            return;
        }

        const root = document.querySelector('.eg-quiz-immersive');

        if (! root) {
            return;
        }

        const key = event.key;

        if (! /^[1-9]$/.test(key)) {
            return;
        }

        const button = root.querySelector(`[data-hotkey="${key}"]`);

        if (! button || button.disabled || button.classList.contains('is-locked')) {
            return;
        }

        event.preventDefault();
        button.click();
    });
}

function rebindQuizUi() {
    document.querySelectorAll('.eg-quiz-option').forEach((el) => {
        if (el.dataset.bound === '1') {
            return;
        }

        el.dataset.bound = '1';
        bindOption(el);
    });

    bindSoundToggle();
}

document.addEventListener('livewire:init', () => {
    bindKeyboard();
    rebindQuizUi();

    Livewire.hook('morph.updated', () => {
        rebindQuizUi();
    });
});
