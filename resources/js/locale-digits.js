const PERSIAN_DIGITS = {
    0: "۰",
    1: "۱",
    2: "۲",
    3: "۳",
    4: "۴",
    5: "۵",
    6: "۶",
    7: "۷",
    8: "۸",
    9: "۹",
};

export function usesLocalizedDigits(locale) {
    return typeof locale === "string" && locale.startsWith("fa");
}

export function formatDigits(value, locale) {
    if (! usesLocalizedDigits(locale)) {
        return String(value);
    }

    try {
        return new Intl.NumberFormat("fa-IR", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(Number(value));
    } catch {
        return String(value).replace(/\d/g, (digit) => PERSIAN_DIGITS[digit] ?? digit);
    }
}

export function padDigits(value, length, locale) {
    const western = String(value).padStart(length, "0");

    if (! usesLocalizedDigits(locale)) {
        return western;
    }

    return western.replace(/\d/g, (digit) => PERSIAN_DIGITS[digit] ?? digit);
}
