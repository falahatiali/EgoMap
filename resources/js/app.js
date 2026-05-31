import "bootstrap/dist/js/bootstrap.bundle.min.js";
import { initFormClickSounds } from "./form-sounds.js";
import { initLocale, syncLocaleFromDocument } from "./locale.js";
import { initNoContactTimers } from "./no-contact-timer.js";
import { initHeroTypedLine } from "./hero-typed-line.js";
import { initProtocolHero } from "./protocol-hero.js";
import { initBootstrapTooltips } from "./tooltips.js";

initLocale();
initBootstrapTooltips();
initFormClickSounds();
initNoContactTimers();
initProtocolHero();
initHeroTypedLine();

document.addEventListener("livewire:navigated", () => {
    syncLocaleFromDocument();
    initNoContactTimers();
    initProtocolHero();
    initHeroTypedLine();
    initBootstrapTooltips();
});

document.addEventListener("livewire:init", () => {
    Livewire.hook("morph.updated", ({ el }) => {
        initBootstrapTooltips(el);
    });
});
