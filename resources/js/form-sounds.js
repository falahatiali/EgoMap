const CLICK_SOUND_URL = '/sounds/mouse-click.mp3';
const CLICK_SOUND_VOLUME = 0.45;

let clickSoundTemplate = null;

function getClickSoundTemplate() {
    if (! clickSoundTemplate) {
        clickSoundTemplate = new Audio(CLICK_SOUND_URL);
        clickSoundTemplate.volume = CLICK_SOUND_VOLUME;
        clickSoundTemplate.preload = 'auto';
    }

    return clickSoundTemplate;
}

export function playFormClickSound() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    try {
        const sound = getClickSoundTemplate().cloneNode();
        sound.volume = CLICK_SOUND_VOLUME;
        void sound.play();
    } catch {
        // Audio not available or blocked until user gesture
    }
}

function isFormClickSoundTarget(target) {
    if (! (target instanceof Element)) {
        return false;
    }

    const form = target.closest('form');

    if (form === null || form.dataset.sound === 'off') {
        return false;
    }

    if (target.closest('input[type="email"], input[type="password"], input[type="text"], input[type="search"], textarea, select')) {
        return false;
    }

    return target.closest(
        'button, input[type="submit"], input[type="checkbox"], input[type="radio"], .eg-otp-digit, .form-check-label',
    ) !== null;
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
