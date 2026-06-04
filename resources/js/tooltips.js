export function initBootstrapTooltips(root = document) {
    if (typeof bootstrap === "undefined") {
        return;
    }

    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        const existing = bootstrap.Tooltip.getInstance(element);

        if (existing) {
            existing.dispose();
        }

        new bootstrap.Tooltip(element);
    });
}
