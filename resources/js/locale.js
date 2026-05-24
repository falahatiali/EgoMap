const SUPPORTED = ['en', 'fa'];

/**
 * @returns {string}
 */
function getBootstrapLink() {
    return document.getElementById('eg-bootstrap');
}

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

    const html = document.documentElement;
    const current = html.getAttribute('lang')?.startsWith('fa') ? 'fa' : 'en';

    if (current === locale) {
        return;
    }

    html.classList.add('eg-locale-switching');

    const isRtl = locale === 'fa';
    html.setAttribute('lang', locale === 'fa' ? 'fa' : 'en');
    html.setAttribute('dir', isRtl ? 'rtl' : 'ltr');

    const bootstrap = getBootstrapLink();

    if (bootstrap) {
        const href = isRtl ? bootstrap.dataset.rtl : bootstrap.dataset.ltr;

        if (href && bootstrap.getAttribute('href') !== href) {
            bootstrap.setAttribute('href', href);
        }
    }

    applyTranslations(locale);
    updateLangButtons(locale);
    updateDirectionalIcons(isRtl);
    updateDocumentMeta(locale);
    persistLocale(locale);

    requestAnimationFrame(() => {
        html.classList.remove('eg-locale-switching');
    });
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
    document.querySelectorAll('[data-locale-switch]').forEach((btn) => {
        const target = btn.getAttribute('data-locale-switch');
        const isActive = target === locale;

        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
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

/**
 * @param {string} locale
 */
function persistLocale(locale) {
    const url = document.querySelector('meta[name="locale-url"]')?.getAttribute('content');

    if (! url) {
        return;
    }

    const endpoint = url.replace('__LOCALE__', locale);

    fetch(endpoint, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    }).catch(() => {});
}

export function initLocale() {
    document.querySelectorAll('[data-locale-switch]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const locale = btn.getAttribute('data-locale-switch');

            if (locale) {
                switchLocale(locale);
            }
        });
    });
}
