const feedNewsOrigin = 'https://www.feednews.me';

function openFeedNews(url) {
    if (!url || !/^https?:\/\//i.test(url)) return;
    chrome.tabs.create({ url: `${feedNewsOrigin}/?import=${encodeURIComponent(url)}&save=1` });
}

chrome.runtime.onInstalled.addListener(() => {
    chrome.contextMenus.create({
        id: 'feednews-save-link',
        title: 'Save to FeedNews',
        contexts: ['link'],
    });
});

chrome.action.onClicked.addListener((tab) => openFeedNews(tab.url));
chrome.contextMenus.onClicked.addListener((info) => {
    if (info.menuItemId === 'feednews-save-link') openFeedNews(info.linkUrl);
});
