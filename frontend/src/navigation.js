export function openFeed(source) {
    const path = `/feed/${encodeURIComponent(source)}`;

    if (window.location.pathname !== path) {
        window.history.pushState({}, '', path);
    }

    window.dispatchEvent(new PopStateEvent('popstate'));
}
