const icons = {
    recent: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/><path d="M12 8v4l2.8 1.8"/></svg>',
    history: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5h14v12H5z"/><path d="M8 3.5h8M8 10h8M8 14h5"/></svg>',
    tags: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5V11l8.4 8.4a1.8 1.8 0 0 0 2.6 0l4.4-4.4a1.8 1.8 0 0 0 0-2.6L11 4H5.5A1.5 1.5 0 0 0 4 5.5Z"/><circle cx="8" cy="8" r="1.2"/></svg>',
    add: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4.2 4.2"/></svg>',
};

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
    const tagButtons = shell.querySelectorAll('.tag-option[style*="--tag-color"]');

    for (const button of tagButtons) {
        if (button.textContent.trim() === name) {
            return button.style.getPropertyValue('--tag-color').trim();
        }
    }

    return '';
}

function closePanels(sidebar, except = '') {
    const classes = ['glass-search-open', 'glass-tags-open', 'glass-add-open'];
    classes.forEach((className) => {
        if (className !== except) sidebar.classList.remove(className);
    });
}

function ensureActiveTagBar(sidebar, select, tagButton, shell) {
    let bar = sidebar.querySelector('.glass-active-tag');
    if (!bar) {
        bar = document.createElement('div');
        bar.className = 'glass-active-tag';

        const dot = document.createElement('span');
        dot.className = 'glass-active-tag-dot';
        bar.append(dot);

        const label = document.createElement('strong');
        label.className = 'glass-active-tag-label';
        bar.append(label);

        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'glass-active-tag-clear';
        clear.setAttribute('aria-label', 'Clear tag filter');
        clear.textContent = '×';
        clear.addEventListener('click', () => {
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncTagState(sidebar, select, tagButton, shell);
        });
        bar.append(clear);
        sidebar.append(bar);
    }

    return bar;
}

function syncTagState(sidebar, select, button, shell) {
    const selectedOption = select.selectedOptions[0];
    const hasTag = select.value !== '';
    const activeBar = ensureActiveTagBar(sidebar, select, button, shell);
    const color = hasTag ? tagColor(shell, selectedOption) : '';

    button.classList.toggle('active', hasTag);
    button.setAttribute('aria-pressed', hasTag ? 'true' : 'false');
    button.title = hasTag && selectedOption ? `Tag: ${tagName(selectedOption)}` : 'Tags';
    sidebar.classList.toggle('glass-tag-active', hasTag);
    activeBar.classList.toggle('visible', hasTag);

    if (hasTag && selectedOption) {
        activeBar.querySelector('.glass-active-tag-label').textContent = tagName(selectedOption);
        activeBar.style.setProperty('--tag-color', color || 'var(--blue)');
    }

    sidebar.querySelectorAll('.glass-tag-option').forEach((option) => {
        option.classList.toggle('active', option.dataset.value === select.value);
    });
}

function renderTagOptions(sidebar, select, panel, tagButton, shell) {
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

    panel.querySelectorAll('.glass-tag-option').forEach((button) => {
        button.addEventListener('click', () => {
            select.value = button.dataset.value || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sidebar.classList.remove('glass-tags-open');
            syncTagState(sidebar, select, tagButton, shell);
        });
    });
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
        const original = syncToOriginal();
        original?.requestSubmit();
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

function enhancePointerTracking(shell) {
    shell.querySelectorAll('.feed-item:not([data-glass-pointer-bound])').forEach((item) => {
        item.dataset.glassPointerBound = 'true';
        item.addEventListener('pointermove', (event) => {
            if (event.pointerType === 'touch') return;

            const bounds = item.getBoundingClientRect();
            item.style.setProperty('--pointer-x', `${event.clientX - bounds.left}px`);
            item.style.setProperty('--pointer-y', `${event.clientY - bounds.top}px`);
            item.classList.add('glass-pointer-active');
        });
        item.addEventListener('pointerleave', () => item.classList.remove('glass-pointer-active'));
    });
}

function enhanceFeedReader() {
    const shell = document.querySelector('.reader-shell');
    if (!shell) return;

    ensureReaderLoadingAction(shell);
    enhancePointerTracking(shell);

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

    let tagButton = tabBar.querySelector('[data-glass-action="tags"]');
    if (!tagButton) {
        tagButton = document.createElement('button');
        tagButton.type = 'button';
        tagButton.setAttribute('aria-label', 'Tags');
        decorateButton(tagButton, 'tags', 'Tags');
        tabBar.insertBefore(tagButton, searchButton);
    }

    let addButton = tabBar.querySelector('[data-glass-action="add"]');
    if (!addButton) {
        addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.setAttribute('aria-label', 'Add saved link');
        decorateButton(addButton, 'add', 'Add');
        tabBar.insertBefore(addButton, searchButton);
    }

    let tagPanel = sidebar.querySelector('.glass-tag-panel');
    if (!tagPanel) {
        tagPanel = document.createElement('div');
        tagPanel.className = 'glass-tag-panel';
        tagPanel.setAttribute('aria-label', 'Filter by tag');
        sidebar.append(tagPanel);
    }

    const addPanel = createAddPanel(sidebar, shell);
    renderTagOptions(sidebar, tagSelect, tagPanel, tagButton, shell);
    syncTagState(sidebar, tagSelect, tagButton, shell);

    if (tagButton.dataset.glassToggleBound !== 'true') {
        tagButton.dataset.glassToggleBound = 'true';
        tagButton.addEventListener('click', () => {
            if (tagSelect.value !== '' && !sidebar.classList.contains('glass-tags-open')) {
                tagSelect.value = '';
                tagSelect.dispatchEvent(new Event('change', { bubbles: true }));
                syncTagState(sidebar, tagSelect, tagButton, shell);
                return;
            }

            const opening = !sidebar.classList.contains('glass-tags-open');
            closePanels(sidebar, opening ? 'glass-tags-open' : '');
            sidebar.classList.toggle('glass-tags-open', opening);
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
        tagSelect.addEventListener('change', () => syncTagState(sidebar, tagSelect, tagButton, shell));
    }
}

export function setupFeedReaderGlass() {
    enhanceFeedReader();

    const app = document.querySelector('#app');
    if (!app) return;

    new MutationObserver(enhanceFeedReader).observe(app, { childList: true, subtree: true });
}
