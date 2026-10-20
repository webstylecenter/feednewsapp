const icons = {
    recent: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/><path d="M12 8v4l2.8 1.8"/></svg>',
    history: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5h14v12H5z"/><path d="M8 3.5h8M8 10h8M8 14h5"/></svg>',
    tags: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5V11l8.4 8.4a1.8 1.8 0 0 0 2.6 0l4.4-4.4a1.8 1.8 0 0 0 0-2.6L11 4H5.5A1.5 1.5 0 0 0 4 5.5Z"/><circle cx="8" cy="8" r="1.2"/></svg>',
    search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4.2 4.2"/></svg>',
};

function decorateButton(button, action, label) {
    if (button.dataset.glassDecorated === 'true') return;

    button.dataset.glassAction = action;
    button.dataset.glassDecorated = 'true';
    button.innerHTML = `<span class="glass-nav-icon">${icons[action]}</span><span class="glass-nav-label">${label}</span>`;
}

function syncTagState(sidebar, select, button) {
    const selectedOption = select.selectedOptions[0];
    const hasTag = select.value !== '';

    button.classList.toggle('active', hasTag);
    button.setAttribute('aria-pressed', hasTag ? 'true' : 'false');
    button.title = hasTag && selectedOption ? `Tag: ${selectedOption.textContent}` : 'Tags';
    sidebar.classList.toggle('glass-tag-active', hasTag);
    sidebar.querySelectorAll('.glass-tag-option').forEach((option) => {
        option.classList.toggle('active', option.dataset.value === select.value);
    });
}

function renderTagOptions(sidebar, select, panel, tagButton) {
    const options = Array.from(select.options).filter((option) => option.value !== '');
    const signature = options.map((option) => `${option.value}:${option.textContent}`).join('|');
    if (panel.dataset.optionsSignature === signature) return;

    panel.dataset.optionsSignature = signature;
    panel.replaceChildren(...options.map((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'glass-tag-option';
        button.dataset.value = option.value;
        button.textContent = option.textContent;
        button.classList.toggle('active', option.value === select.value);
        return button;
    }));

    if (options.length === 0) {
        const empty = document.createElement('span');
        empty.className = 'glass-tag-empty';
        empty.textContent = 'No tags available';
        panel.append(empty);
    }

    panel.querySelectorAll('.glass-tag-option').forEach((button) => {
        button.addEventListener('click', () => {
            select.value = button.dataset.value || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sidebar.classList.remove('glass-tags-open');
            syncTagState(sidebar, select, tagButton);
        });
    });
}

function enhanceFeedReader() {
    const shell = document.querySelector('.reader-shell');
    const sidebar = shell?.querySelector('.feed-sidebar');
    const tabBar = sidebar?.querySelector('.tab-bar');
    const searchForm = sidebar?.querySelector('.search-form');
    const tagSelect = sidebar?.querySelector('.sidebar-tools > select');
    if (!shell || !sidebar || !tabBar || !searchForm || !tagSelect) return;

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

            sidebar.classList.remove('glass-tags-open');
            sidebar.classList.toggle('glass-search-open');
            if (sidebar.classList.contains('glass-search-open')) {
                window.requestAnimationFrame(() => searchForm.querySelector('input')?.focus());
            }
        }, true);
    }

    let tagButton = tabBar.querySelector('[data-glass-action="tags"]');
    if (!tagButton) {
        tagButton = document.createElement('button');
        tagButton.type = 'button';
        tagButton.setAttribute('aria-label', 'Tags');
        decorateButton(tagButton, 'tags', 'Tags');
        tabBar.insertBefore(tagButton, searchButton);
    }

    let tagPanel = sidebar.querySelector('.glass-tag-panel');
    if (!tagPanel) {
        tagPanel = document.createElement('div');
        tagPanel.className = 'glass-tag-panel';
        tagPanel.setAttribute('aria-label', 'Filter by tag');
        sidebar.append(tagPanel);
    }

    renderTagOptions(sidebar, tagSelect, tagPanel, tagButton);
    syncTagState(sidebar, tagSelect, tagButton);

    if (tagButton.dataset.glassToggleBound !== 'true') {
        tagButton.dataset.glassToggleBound = 'true';
        tagButton.addEventListener('click', () => {
            if (tagSelect.value !== '' && !sidebar.classList.contains('glass-tags-open')) {
                tagSelect.value = '';
                tagSelect.dispatchEvent(new Event('change', { bubbles: true }));
                syncTagState(sidebar, tagSelect, tagButton);
                return;
            }

            sidebar.classList.remove('glass-search-open');
            sidebar.classList.toggle('glass-tags-open');
        });
    }

    if (tagSelect.dataset.glassSyncBound !== 'true') {
        tagSelect.dataset.glassSyncBound = 'true';
        tagSelect.addEventListener('change', () => syncTagState(sidebar, tagSelect, tagButton));
    }
}

export function setupFeedReaderGlass() {
    enhanceFeedReader();

    const app = document.querySelector('#app');
    if (!app) return;

    new MutationObserver(enhanceFeedReader).observe(app, { childList: true, subtree: true });
}
