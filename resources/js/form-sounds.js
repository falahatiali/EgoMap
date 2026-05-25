const CLICK_SOUND_URL = '/sounds/mouse-click.mp3';
const CLICK_SOUND_VOLUME = 0.45;
const POOL_SIZE = 4;

/** @type {HTMLAudioElement[]} */
let clickSoundPool = [];
let poolIndex = 0;

function ensureClickSoundPool() {
    if (clickSoundPool.length > 0) {
        return;
    }

    clickSoundPool = Array.from({ length: POOL_SIZE }, () => {
        const audio = new Audio(CLICK_SOUND_URL);
        audio.preload = 'auto';
        audio.volume = CLICK_SOUND_VOLUME;

        return audio;
    });
}

function playFromPool() {
    ensureClickSoundPool();

    const sound = clickSoundPool[poolIndex];
    poolIndex = (poolIndex + 1) % POOL_SIZE;

    sound.pause();
    sound.currentTime = 0;

    void sound.play().catch(() => {
        const fallback = new Audio(CLICK_SOUND_URL);
        fallback.volume = CLICK_SOUND_VOLUME;
        void fallback.play().catch(() => {});
    });
}

export function playFormClickSound() {
    try {
        playFromPool();
    } catch {
        // Audio not available
    }
}

function isSoundDisabled(container) {
    return container instanceof Element && container.dataset.sound === 'off';
}

function isFormClickSoundTarget(target) {
    if (! (target instanceof Element)) {
        return false;
    }

    if (target.closest('.eg-otp-digit')) {
        const form = target.closest('form');

        return form !== null && ! isSoundDisabled(form);
    }

    const form = target.closest('form');

    if (form !== null && ! isSoundDisabled(form)) {
        if (target.closest('input[type="email"], input[type="password"], input[type="text"], input[type="search"], textarea, select')) {
            return false;
        }

        return target.closest(
            'button, input[type="submit"], input[type="checkbox"], input[type="radio"], .form-check-label',
        ) !== null;
    }

    if (target.closest('.eg-auth-card')) {
        return target.closest('button, input[type="submit"], .btn') !== null;
    }

    return false;
}

export function initFormClickSounds() {
    document.addEventListener(
        'click',
        (event) => {
            if (! isFormClickSoundTarget(event.target)) {
                return;
            }

            playFormClickSound();
        },
        { capture: true },
    );
}
