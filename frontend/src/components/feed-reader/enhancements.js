import { api } from '../../api.js';
import { getTheme, toggleTheme } from '../../theme.js';

const icons = {
    recent: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/><path d="M12 8v4l2.8 1.8"/></svg>',
    history: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5h14v12H5z"/><path d="M8 3.5h8M8 10h8M8 14h5"/></svg>',
    filter: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>',
    add: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4.2 4.2"/></svg>',
};

let enhanceFrame = 0;

function decorateButton(button, action, label) {
    if (button.dataset.glassDecorated === 'true') return;

    button.dataset.glassAction = action;
    button.dataset.glassDecorated = 'true';
    button.innerHTML = `<span class="glass-nav-icon">${icons[action]}</span><span class="glass-nav-label">${label}</span>`;
}

function tagName(option) {
    return option?.textContent?.replace(/\s+\(\d+\)\s*$/, '').trim() || '';
}

function tagColor(shell, option) {
    const name = tagName(option);

    for (const button of shell.querySelectorAll('.tag-option[style*="--tag-color"]')) {
        if (button.textContent.trim() === name) {
            return button.style.getPropertyValue('--tag-color').trim();
        }
    }

    return '';
}

function closePanels(sidebar, except = '') {
    for (const className of ['glass-search-open', 'glass-filter-open', 'glass-add-open']) {
        if (className !== except) sidebar.classList.remove(className);
    }
}

function openFeed(source) {
    const path = `/feed/${encodeURIComponent(source)}`;
    if (window.location.pathname !== path) {
        window.history.pushState({}, '', path);
    }
    window.dispatchEvent(new PopStateEvent('popstate'));
}

function ensureThemeToggle(shell) {
    const headerActions = shell.querySelector('.header-actions');
    if (!headerActions) return;

    let button = headerActions.querySelector('[data-theme-toggle]');
    if (!button) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'icon-button theme-toggle';
        button.dataset.themeToggle = 'true';
        headerActions.insertBefore(button, headerActions.querySelector('.profile-wrap'));
        button.addEventListener('click', () => {
            toggleTheme();
            syncThemeToggle(button);
        });
    }

    syncThemeToggle(button);
}

function syncThemeToggle(button) {
    const theme = getTheme();
    button.textContent = theme === 'dark' ? '☀' : '☾';
    button.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    button.setAttribute('aria-label', button.title);
}

function ensureActiveTagBar(sidebar, select) {
    let bar = sidebar.querySelector('.glass-active-tag');
    if (bar) return bar;

    bar = document.createElement('div');
    bar.className = 'glass-active-tag';
    bar.innerHTML = `
        <span class="glass-active-tag-dot"></span>
        <strong class="glass-active-tag-label"></strong>
        <button type="button" class="glass-active-tag-clear" aria-label="Clear tag filter">×</button>
    `;
    bar.querySelector('.glass-active-tag-clear').addEventListener('click', () => {
        select.value = '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    sidebar.append(bar);

    return bar;
}

function syncTagState(sidebar, select, button, shell) {
    const selectedOption = select.selectedOptions[0];
    const hasTag = select.value !== '';
    const activeBar = ensureActiveTagBar(sidebar, select);

    button.classList.toggle('active', hasTag);
    button.setAttribute('aria-pressed', hasTag ? 'true' : 'false');
    button.title = hasTag && selectedOption ? `Tag: ${tagName(selectedOption)}` : 'Filter';
    sidebar.classList.toggle('glass-tag-active', hasTag);
    activeBar.classList.toggle('visible', hasTag);

    if (hasTag && selectedOption) {
        const label = activeBar.querySelector('.glass-active-tag-label');
        const name = tagName(selectedOption);
        const color = tagColor(shell, selectedOption) || 'var(--blue)';

        if (label.textContent !== name) label.textContent = name;
        if (activeBar.style.getPropertyValue('--tag-color') !== color) {
            activeBar.style.setProperty('--tag-color', color);
        }
    }

    for (const option of sidebar.querySelectorAll('.glass-tag-option')) {
        option.classList.toggle('active', option.dataset.value === select.value);
    }
}

function renderTagOptions(sidebar, select, panel, shell) {
    const options = Array.from(select.options).filter((option) => option.value !== '');
    const signature = options.map((option) => `${option.value}:${option.textContent}:${tagColor(shell, option)}`).join('|');
    if (panel.dataset.optionsSignature === signature) return;

    panel.dataset.optionsSignature = signature;
    panel.replaceChildren(...options.map((option) => {
        const button = document.createElement('button');
        const color = tagColor(shell, option);
        button.type = 'button';
        button.className = 'glass-tag-option';
        button.dataset.value = option.value;
        button.textContent = option.textContent;
        button.classList.toggle('active', option.value === select.value);
        if (color !== '') button.style.setProperty('--tag-color', color);
        return button;
    }));

    if (options.length === 0) {
        const empty = document.createElement('span');
        empty.className = 'glass-tag-empty';
        empty.textContent = 'No tags available';
        panel.append(empty);
    }

    for (const button of panel.querySelectorAll('.glass-tag-option')) {
        button.addEventListener('click', () => {
            select.value = button.dataset.value || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sidebar.classList.remove('glass-filter-open');
        });
    }
}

function activateFilterTab(panel, tab) {
    panel.dataset.activeTab = tab;
    for (const button of panel.querySelectorAll('.glass-filter-tabs button')) {
        const active = button.dataset.filterTab === tab;
        button.classList.toggle('active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    }
}

async function renderFeedOptions(panel, sidebar) {
    const feedPanel = panel.querySelector('.glass-feed-filter-list');
    if (!feedPanel || feedPanel.dataset.loading === 'true') return;

    feedPanel.dataset.loading = 'true';
    try {
        const dashboard = await api('/api/dashboard');
        const feeds = dashboard.feeds || [];
        const signature = feeds.map((feed) => `${feed.source}:${feed.name}:${feed.color}`).join('|');
        if (feedPanel.dataset.feedSignature === signature) return;

        feedPanel.dataset.feedSignature = signature;
        feedPanel.replaceChildren(...feeds.map((feed) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'glass-feed-filter-option';
            button.style.setProperty('--feed-color', feed.color || 'var(--blue)');
            button.innerHTML = '<span class="feed-dot"></span><strong></strong>';
            button.querySelector('strong').textContent = feed.name;
            button.addEventListener('click', () => {
                sidebar.classList.remove('glass-filter-open');
                openFeed(feed.source);
            });
            return button;
        }));

        if (feeds.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'glass-tag-empty';
            empty.textContent = 'No feeds configured';
            feedPanel.append(empty);
        }
    } catch {
        feedPanel.textContent = 'Feeds could not be loaded.';
    } finally {
        feedPanel.dataset.loading = 'false';
    }
}

function createFilterPanel(sidebar, select, shell) {
    let panel = sidebar.querySelector('.glass-filter-panel');
    if (panel) return panel;

    panel = document.createElement('div');
    panel.className = 'glass-filter-panel';
    panel.dataset.activeTab = 'tags';
    panel.innerHTML = `
        <div class="glass-filter-tabs" role="tablist" aria-label="Filter type">
            <button type="button" class="active" data-filter-tab="tags" role="tab" aria-selected="true">Tags</button>
            <button type="button" data-filter-tab="feeds" role="tab" aria-selected="false">Feeds</button>
        </div>
        <div class="glass-filter-content">
            <div class="glass-tag-options" data-filter-content="tags"></div>
            <div class="glass-feed-filter-list" data-filter-content="feeds"></div>
        </div>
    `;
    sidebar.append(panel);

    const tagPanel = panel.querySelector('.glass-tag-options');
    renderTagOptions(sidebar, select, tagPanel, shell);

    for (const button of panel.querySelectorAll('.glass-filter-tabs button')) {
        button.addEventListener('click', () => {
            const tab = button.dataset.filterTab || 'tags';
            activateFilterTab(panel, tab);
            if (tab === 'feeds') renderFeedOptions(panel, sidebar);
        });
    }

    return panel;
}

function findOriginalAddForm(shell) {
    return Array.from(shell.querySelectorAll('dialog .dialog-form')).find((form) => (
        form.querySelector('h2')?.textContent.trim() === 'Save a link'
    )) || null;
}

function setFormValue(input, value) {
    if (!input) return;

    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function createAddPanel(sidebar, shell) {
    let panel = sidebar.querySelector('.glass-add-panel');
    if (panel) return panel;

    panel = document.createElement('div');
    panel.className = 'glass-add-panel';
    panel.innerHTML = `
        <form class="glass-add-form">
            <label>URL<input name="url" type="url" required placeholder="https://example.com/article"></label>
            <label>Title<input name="title" type="text" placeholder="Title"></label>
            <label>Description<textarea name="description" rows="3" placeholder="Description"></textarea></label>
            <div class="glass-add-actions">
                <button type="button" class="glass-metadata-button">Get metadata</button>
                <button type="submit" class="glass-save-button">Save</button>
            </div>
        </form>
    `;
    sidebar.append(panel);

    const form = panel.querySelector('form');
    const url = form.elements.namedItem('url');
    const title = form.elements.namedItem('title');
    const description = form.elements.namedItem('description');

    const syncToOriginal = () => {
        const original = findOriginalAddForm(shell);
        if (!original) return null;

        setFormValue(original.querySelector('input[type="url"]'), url.value);
        setFormValue(original.querySelectorAll('input')[1], title.value);
        setFormValue(original.querySelector('textarea'), description.value);
        return original;
    };

    const syncFromOriginal = () => {
        const original = findOriginalAddForm(shell);
        if (!original) return;

        url.value = original.querySelector('input[type="url"]')?.value || '';
        title.value = original.querySelectorAll('input')[1]?.value || '';
        description.value = original.querySelector('textarea')?.value || '';
    };

    const refreshFromOriginal = () => {
        let attempts = 0;
        const timer = window.setInterval(() => {
            syncFromOriginal();
            attempts += 1;
            if (attempts >= 12) window.clearInterval(timer);
        }, 250);
    };

    form.addEventListener('input', syncToOriginal);
    url.addEventListener('blur', () => {
        const original = syncToOriginal();
        original?.querySelector('input[type="url"]')?.dispatchEvent(new FocusEvent('blur', { bubbles: true }));
        refreshFromOriginal();
    });

    panel.querySelector('.glass-metadata-button').addEventListener('click', () => {
        const original = syncToOriginal();
        const metadataButton = Array.from(original?.querySelectorAll('button[type="button"]') || []).find((button) => (
            button.textContent.trim() === 'Get metadata'
        ));
        metadataButton?.click();
        refreshFromOriginal();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        syncToOriginal()?.requestSubmit();
        sidebar.classList.remove('glass-add-open');
        window.setTimeout(syncFromOriginal, 350);
    });

    panel.syncFromOriginal = syncFromOriginal;
    return panel;
}

function ensureReaderLoadingAction(shell) {
    const loading = shell.querySelector('.reader-loading');
    if (!loading || loading.querySelector('.reader-loading-original')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'primary reader-loading-original';
    button.textContent = 'Open originele website ↗';
    button.addEventListener('click', () => {
        shell.querySelector('.content-toolbar-actions button[title="Open original website"]')?.click();
    });
    loading.append(button);
}

function enhanceFeedReader() {
    const shell = document.querySelector('.reader-shell');
    if (!shell) return;

    ensureReaderLoadingAction(shell);
    ensureThemeToggle(shell);

    const sidebar = shell.querySelector('.feed-sidebar');
    const tabBar = sidebar?.querySelector('.tab-bar');
    const searchForm = sidebar?.querySelector('.search-form');
    const tagSelect = sidebar?.querySelector('.sidebar-tools > select');
    if (!sidebar || !tabBar || !searchForm || !tagSelect) return;

    shell.classList.add('glass-reader-ready');
    sidebar.classList.add('glass-feed-ready');

    const buttons = Array.from(tabBar.querySelectorAll(':scope > button'));
    const recentButton = buttons.find((button) => button.dataset.glassAction === 'recent' || button.textContent.trim() === 'Recent');
    const historyButton = buttons.find((button) => button.dataset.glassAction === 'history' || button.textContent.trim() === 'History');
    const searchButton = buttons.find((button) => button.dataset.glassAction === 'search' || button.textContent.trim() === 'Search');
    if (!recentButton || !historyButton || !searchButton) return;

    decorateButton(recentButton, 'recent', 'Recent');
    decorateButton(historyButton, 'history', 'History');
    decorateButton(searchButton, 'search', 'Search');

    if (searchButton.dataset.glassToggleBound !== 'true') {
        searchButton.dataset.glassToggleBound = 'true';
        searchButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();

            const opening = !sidebar.classList.contains('glass-search-open');
            closePanels(sidebar, opening ? 'glass-search-open' : '');
            sidebar.classList.toggle('glass-search-open', opening);
            if (opening) {
                window.requestAnimationFrame(() => searchForm.querySelector('input')?.focus());
            }
        }, true);
    }

    let filterButton = tabBar.querySelector('[data-glass-action="filter"]');
    if (!filterButton) {
        filterButton = document.createElement('button');
        filterButton.type = 'button';
        filterButton.setAttribute('aria-label', 'Filter');
        decorateButton(filterButton, 'filter', 'Filter');
        tabBar.insertBefore(filterButton, searchButton);
    }

    let addButton = tabBar.querySelector('[data-glass-action="add"]');
    if (!addButton) {
        addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.setAttribute('aria-label', 'Add saved link');
        decorateButton(addButton, 'add', 'Add');
        tabBar.insertBefore(addButton, searchButton);
    }

    const filterPanel = createFilterPanel(sidebar, tagSelect, shell);
    const tagPanel = filterPanel.querySelector('.glass-tag-options');
    const addPanel = createAddPanel(sidebar, shell);
    renderTagOptions(sidebar, tagSelect, tagPanel, shell);
    syncTagState(sidebar, tagSelect, filterButton, shell);

    if (filterButton.dataset.glassToggleBound !== 'true') {
        filterButton.dataset.glassToggleBound = 'true';
        filterButton.addEventListener('click', () => {
            const opening = !sidebar.classList.contains('glass-filter-open');
            closePanels(sidebar, opening ? 'glass-filter-open' : '');
            sidebar.classList.toggle('glass-filter-open', opening);
            if (opening && filterPanel.dataset.activeTab === 'feeds') {
                renderFeedOptions(filterPanel, sidebar);
            }
        });
    }

    if (addButton.dataset.glassToggleBound !== 'true') {
        addButton.dataset.glassToggleBound = 'true';
        addButton.addEventListener('click', () => {
            const opening = !sidebar.classList.contains('glass-add-open');
            closePanels(sidebar, opening ? 'glass-add-open' : '');
            sidebar.classList.toggle('glass-add-open', opening);
            if (opening) {
                addPanel.syncFromOriginal?.();
                window.requestAnimationFrame(() => addPanel.querySelector('input[type="url"]')?.focus());
            }
        });
    }

    if (tagSelect.dataset.glassSyncBound !== 'true') {
        tagSelect.dataset.glassSyncBound = 'true';
        tagSelect.addEventListener('change', () => syncTagState(sidebar, tagSelect, filterButton, shell));
    }
}

function scheduleEnhanceFeedReader() {
    if (enhanceFrame !== 0) return;

    enhanceFrame = window.requestAnimationFrame(() => {
        enhanceFrame = 0;
        enhanceFeedReader();
    });
}

export function setupFeedReaderGlass() {
    enhanceFeedReader();

    const app = document.querySelector('#app');
    if (!app) return;

    new MutationObserver(scheduleEnhanceFeedReader).observe(app, { childList: true, subtree: true });
}
