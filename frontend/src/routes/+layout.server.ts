export const load = async ({ fetch }) => {
    const res = await fetch('/api/feeds', { credentials: 'include' });
    if (!res.ok) return { feeds: [{
            title: 'Feed title',
            id: 12,
            read: true,
        }] };
    const feeds = await res.json();
    return { feeds };
};