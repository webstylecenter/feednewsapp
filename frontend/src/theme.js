const storageKey = 'feednews.theme';

function storedTheme() {
    const theme = window.localStorage.getItem(storageKey);

    return theme === 'light' || theme === 'dark' ? theme : null;
}

export function applyTheme() {
    const theme = storedTheme();
    if (theme !== null) {
        document.documentElement.dataset.theme = theme;
        return;
    }

    delete document.documentElement.dataset.theme;
}

export function getTheme() {
    const theme = storedTheme();
    if (theme !== null) {
        return theme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function toggleTheme() {
    const theme = getTheme() === 'dark' ? 'light' : 'dark';
    window.localStorage.setItem(storageKey, theme);
    document.documentElement.dataset.theme = theme;

    return theme;
}
