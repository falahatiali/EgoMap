const SUPPORTED = ['en', 'fa'];

let localeDelegationBound = false;

/**
 * @returns {Record<string, Record<string, string>>|null}
 */
function getTranslations() {
    const el = document.getElementById('eg-i18n');

    if (! el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

/**
 * @param {string} locale
 */
export function switchLocale(locale) {
    if (! SUPPORTED.includes(locale)) {
        return;
    }

    const link = document.querySelector(`[data-locale-switch="${locale}"]`);

    if (link instanceof HTMLAnchorElement && link.href) {
        window.location.assign(link.href);

        return;
    }

    const path = window.location.pathname.replace(/^\/(en|fa)(?=\/|$)/, `/${locale}`);

    window.location.assign(path === window.location.pathname ? `/${locale}` : path);
}

/**
 * @param {string} locale
 */
function applyTranslations(locale) {
    const bundle = getTranslations();

    if (! bundle?.[locale]) {
        return;
    }

    const strings = bundle[locale];

    document.querySelectorAll('[data-i18n]').forEach((el) => {
        const key = el.getAttribute('data-i18n');

        if (key && strings[key] !== undefined) {
            el.textContent = strings[key];
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
        const key = el.getAttribute('data-i18n-placeholder');

        if (key && strings[key] !== undefined && el instanceof HTMLInputElement) {
            el.placeholder = strings[key];
        }
    });

    document.querySelectorAll('[data-locale-field]').forEach((el) => {
        const value = el.getAttribute(`data-${locale}`);

        if (value) {
            el.textContent = value;
        }
    });
}

/**
 * @param {string} locale
 */
function updateLangButtons(locale) {
    document.querySelectorAll('[data-locale-switch]').forEach((link) => {
        const target = link.getAttribute('data-locale-switch');
        const isActive = target === locale;

        link.classList.toggle('active', isActive);

        if (link instanceof HTMLAnchorElement) {
            link.setAttribute('aria-current', isActive ? 'page' : 'false');
        }
    });
}

/**
 * @param {boolean} isRtl
 */
function updateDirectionalIcons(isRtl) {
    document.querySelectorAll('[data-icon-directional]').forEach((icon) => {
        icon.classList.remove('fa-arrow-right', 'fa-arrow-left');
        icon.classList.add(isRtl ? 'fa-arrow-left' : 'fa-arrow-right');
    });
}

/**
 * @param {string} locale
 */
function updateDocumentMeta(locale) {
    const bundle = getTranslations();

    if (! bundle?.[locale]) {
        return;
    }

    const title = bundle[locale]['common.brand'];
    const tagline = bundle[locale]['common.tagline'];

    if (title && tagline) {
        document.title = `${title} — ${tagline}`;
    }

    const meta = document.querySelector('meta[name="description"]');

    if (meta && tagline) {
        meta.setAttribute('content', tagline);
    }
}

export function initLocale() {
    if (localeDelegationBound) {
        return;
    }

    localeDelegationBound = true;

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest('[data-locale-switch]')
            : null;

        if (! link || ! (link instanceof HTMLAnchorElement)) {
            return;
        }

        const locale = link.getAttribute('data-locale-switch');
        const current = document.documentElement.getAttribute('lang')?.startsWith('fa') ? 'fa' : 'en';

        if (! locale || locale === current) {
            return;
        }

        event.preventDefault();
        switchLocale(locale);
    });
}

/**
 * After Livewire navigation, sync icons only (copy comes from the server).
 */
export function syncLocaleFromDocument() {
    const locale = document.documentElement.getAttribute('lang')?.startsWith('fa') ? 'fa' : 'en';
    const isRtl = locale === 'fa';

    updateLangButtons(locale);
    updateDirectionalIcons(isRtl);
}
