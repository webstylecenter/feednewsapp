const weatherNames = {
    clear: 'sun',
    sun: 'sun',
    clouds: 'cloud',
    partly_cloud: 'partly_cloud',
    rain: 'rain',
    snow: 'snow',
    thunderstorm: 'thunder',
    unknown: 'cloud',
};

export function weatherIcon(type, blue = false) {
    const name = weatherNames[type] || 'cloud';
    return `/images/${name}${blue ? '-blue' : ''}.png`;
}

export function faviconUrl(url) {
    try {
        const parsed = new URL(url);
        if (!['http:', 'https:'].includes(parsed.protocol)) return '';

        return `${parsed.origin}/favicon.ico`;
    } catch {
        return '';
    }
}

const feedIcons = [
    ['youtube', '▶'],
    ['video', '▶'],
    ['game', '◆'],
    ['steam', '◆'],
    ['github', '⌘'],
    ['code', '⌘'],
    ['calendar', '▦'],
    ['shopping', '▤'],
    ['cart', '▤'],
    ['android', '◈'],
    ['apple', '●'],
    ['newspaper', '▰'],
    ['rss', '◔'],
];

export function feedIcon(icon) {
    const value = String(icon || '').trim();
    if (value === '') return '•';
    if (!value.includes('fa-') && !value.includes('fa ')) return value;

    const normalized = value.toLowerCase();
    return feedIcons.find(([name]) => normalized.includes(name))?.[1] || '◔';
}
