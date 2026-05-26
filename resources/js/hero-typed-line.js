/**
 * Subtle hero typewriter for rotating words (no external library).
 */
export function initHeroTypedLine(root = document) {
    const output = root.querySelector("[data-hero-typed]");

    if (! output || output.dataset.heroTypedReady === "1") {
        return;
    }

    output.dataset.heroTypedReady = "1";

    let words = [];

    try {
        words = JSON.parse(output.dataset.words ?? "[]");
    } catch {
        words = [];
    }

    words = words.map((w) => String(w)).filter((w) => w.length > 0);

    if (words.length === 0) {
        return;
    }

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        output.textContent = words[0];

        return;
    }

    const TYPE_MS = 50;
    const BACK_MS = 25;
    const HOLD_MS = 1500;
    const START_MS = 500;

    let wordIndex = 0;
    let charIndex = 0;
    let deleting = false;
    let timeoutId = null;

    const tick = () => {
        const word = words[wordIndex] ?? "";

        if (! deleting) {
            output.textContent = word.slice(0, charIndex + 1);
            charIndex += 1;

            if (charIndex >= word.length) {
                deleting = true;
                timeoutId = window.setTimeout(tick, HOLD_MS);

                return;
            }

            timeoutId = window.setTimeout(tick, TYPE_MS);

            return;
        }

        output.textContent = word.slice(0, charIndex);
        charIndex -= 1;

        if (charIndex < 0) {
            deleting = false;
            charIndex = 0;
            wordIndex = (wordIndex + 1) % words.length;
            timeoutId = window.setTimeout(tick, TYPE_MS);

            return;
        }

        timeoutId = window.setTimeout(tick, BACK_MS);
    };

    timeoutId = window.setTimeout(tick, START_MS);

    output.addEventListener(
        "livewire:navigating",
        () => {
            if (timeoutId !== null) {
                window.clearTimeout(timeoutId);
            }
        },
        { once: true },
    );
}
