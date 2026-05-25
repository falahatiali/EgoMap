import "bootstrap/dist/js/bootstrap.bundle.min.js";
import { initFormClickSounds } from "./form-sounds.js";
import { initLocale } from "./locale.js";
import { initNoContactTimers } from "./no-contact-timer.js";

initLocale();
initFormClickSounds();
initNoContactTimers();

document.addEventListener("livewire:navigated", () => {
    initNoContactTimers();
});
