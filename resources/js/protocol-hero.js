/**
 * Editorial typewriter: gentle pace, luxury insight card + optional CTA reveal.
 */
export function initProtocolHero(root = document) {
    const output = root.querySelector("[data-terminal-typewriter]");

    if (! output || output.dataset.terminalReady === "1") {
        return;
    }

    output.dataset.terminalReady = "1";

    const stage = output.closest(".eg-terminal__stage");
    const ctaBlock = stage?.querySelector("[data-terminal-cta]");

    let sequence = [];

    try {
        sequence = JSON.parse(output.dataset.sequence ?? "[]");
    } catch {
        sequence = [];
    }

    const TYPE_MS = 42;
    const CLEAR_MS = 14;
    const HOLD_MS = 1000;
    const GAP_MS = 200;

    const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    const type = async (text) => {
        output.textContent = "";

        for (let i = 0; i < text.length; i++) {
            output.textContent = text.slice(0, i + 1);
            await sleep(TYPE_MS);
        }
    };

    const clear = async () => {
        const text = output.textContent;

        for (let i = text.length; i >= 0; i--) {
            output.textContent = text.slice(0, i);
            await sleep(CLEAR_MS);
        }
    };

    const run = async () => {
        if (! Array.isArray(sequence) || sequence.length === 0) {
            revealCta(ctaBlock);

            return;
        }

        for (const item of sequence) {
            const text = String(item.text ?? "");
            const tone = String(item.tone ?? "normal");
            const shouldClear = Boolean(item.clear);

            const isEmphasis = tone === "alert" || tone === "emphasis";
            output.classList.toggle("is-alert", isEmphasis);
            output.classList.toggle("is-emphasis", isEmphasis);
            output.classList.toggle("is-normal", ! isEmphasis);

            await type(text);

            if (shouldClear) {
                await sleep(HOLD_MS);
                await clear();
                await sleep(GAP_MS);
            }
        }

        revealCta(ctaBlock);
    };

    run();
}

/**
 * @param {HTMLElement | null | undefined} block
 */
function revealCta(block) {
    if (! block) {
        return;
    }

    block.removeAttribute("hidden");
    block.classList.add("is-visible");
}
