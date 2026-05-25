/**
 * Client-side countdown synced to server anchor timestamps.
 * Survives refresh; authoritative state remains on the backend (Livewire poll).
 */
export function initNoContactTimers(root = document) {
    root.querySelectorAll('#eg-no-contact-timer').forEach((element) => {
        if (element.dataset.ncInitialized === '1') {
            return;
        }

        element.dataset.ncInitialized = '1';

        const targetIso = element.dataset.targetEndsAt;
        const serverNowIso = element.dataset.serverNow;

        if (! targetIso || ! serverNowIso) {
            return;
        }

        const targetMs = Date.parse(targetIso);
        const serverNowMs = Date.parse(serverNowIso);
        const clientNowMs = Date.now();
        const offsetMs = serverNowMs - clientNowMs;

        const parts = {
            days: element.querySelector('[data-nc-part="days"]'),
            hours: element.querySelector('[data-nc-part="hours"]'),
            minutes: element.querySelector('[data-nc-part="minutes"]'),
            seconds: element.querySelector('[data-nc-part="seconds"]'),
        };

        const progressRing = element.querySelector('.eg-nc-ring-progress');

        const tick = () => {
            const nowMs = Date.now() + offsetMs;
            let remainingMs = Math.max(0, targetMs - nowMs);
            const totalSeconds = Math.floor(remainingMs / 1000);

            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            if (parts.days) {
                parts.days.textContent = String(days).padStart(2, '0');
            }

            if (parts.hours) {
                parts.hours.textContent = String(hours).padStart(2, '0');
            }

            if (parts.minutes) {
                parts.minutes.textContent = String(minutes).padStart(2, '0');
            }

            if (parts.seconds) {
                parts.seconds.textContent = String(seconds).padStart(2, '0');
            }

            if (progressRing) {
                const startedAttr = element.dataset.streakStartedAt;
                if (startedAttr) {
                    const startedMs = Date.parse(startedAttr);
                    const totalMs = Math.max(targetMs - startedMs, 1);
                    const elapsedMs = Math.min(Math.max(nowMs - startedMs, 0), totalMs);
                    const percent = Math.round((elapsedMs / totalMs) * 100);
                    progressRing.style.setProperty('--eg-nc-progress', String(percent));
                }
            }
        };

        tick();
        window.setInterval(tick, 1000);
    });
}
