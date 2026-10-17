export function applyTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
        document.documentElement.dataset.theme = theme;
        return;
    }

    delete document.documentElement.dataset.theme;
}
