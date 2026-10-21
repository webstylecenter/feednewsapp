let feednewsOrigin = '';
let csrfToken = '';

const form = document.querySelector('#save-form');
const urlInput = document.querySelector('#url');
const titleInput = document.querySelector('#title');
const descriptionInput = document.querySelector('#description');
const saveButton = document.querySelector('#save-button');
const saveError = document.querySelector('#save-error');
const pageError = document.querySelector('#page-error');
const authNotice = document.querySelector('#auth-notice');
const loginButton = document.querySelector('#login-button');
const success = document.querySelector('#success');

function showPageError(message) {
    pageError.textContent = message;
    pageError.hidden = false;
}

function showSaveError(message) {
    saveError.textContent = message;
    saveError.hidden = false;
}

function clearSaveError() {
    saveError.textContent = '';
    saveError.hidden = true;
}

function setSaving(saving) {
    saveButton.disabled = saving;
    saveButton.classList.toggle('loading', saving);
}

function showAuthRequired() {
    authNotice.hidden = false;
    saveButton.disabled = true;
}

async function loadConfiguration() {
    const response = await fetch(chrome.runtime.getURL('config.json'));
    if (!response.ok) {
        throw new Error('The Feednews extension configuration could not be loaded.');
    }

    const config = await response.json();
    if (typeof config.feednewsOrigin !== 'string' || config.feednewsOrigin === '') {
        throw new Error('The Feednews URL is missing from the extension configuration.');
    }

    feednewsOrigin = config.feednewsOrigin.replace(/\/$/, '');
}

async function loadCurrentPage() {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (!tab || typeof tab.id !== 'number' || !/^https?:\/\//i.test(tab.url || '')) {
        showPageError('This browser page cannot be saved to Feednews.');
        saveButton.disabled = true;
        return;
    }

    urlInput.value = tab.url || '';
    titleInput.value = (tab.title || '').slice(0, 255);

    try {
        const [execution] = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            func: () => {
                const meta = (selector) => document.querySelector(selector)?.getAttribute('content')?.trim() || '';
                const normalize = (value) => value.replace(/\s+/g, ' ').trim();
                const paragraph = document.querySelector('article p, main p, p')?.textContent || '';
                const title = meta('meta[property="og:title"]')
                    || meta('meta[name="twitter:title"]')
                    || document.title;
                const description = meta('meta[name="description"]')
                    || meta('meta[property="og:description"]')
                    || meta('meta[name="twitter:description"]')
                    || paragraph;

                return {
                    url: window.location.href,
                    title: normalize(title).slice(0, 255),
                    description: normalize(description).slice(0, 500),
                };
            },
        });

        if (execution?.result) {
            urlInput.value = execution.result.url || urlInput.value;
            titleInput.value = execution.result.title || titleInput.value;
            descriptionInput.value = execution.result.description || '';
        }
    } catch {
        showPageError('The page metadata could not be read. You can still edit and save the URL and title below.');
    }
}

async function loadSession() {
    const response = await fetch(`${feednewsOrigin}/api/auth/session`, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Feednews returned HTTP ${response.status}.`);
    }

    const session = await response.json();
    if (!session.user || typeof session.csrfToken !== 'string' || session.csrfToken === '') {
        showAuthRequired();
        return;
    }

    csrfToken = session.csrfToken;
}

async function responseError(response) {
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        return `Feednews returned HTTP ${response.status}.`;
    }

    const data = await response.json();
    return data.detail || data.message || `Feednews returned HTTP ${response.status}.`;
}

async function saveItem(event) {
    event.preventDefault();
    clearSaveError();

    if (csrfToken === '') {
        showAuthRequired();
        return;
    }

    setSaving(true);

    try {
        const response = await fetch(`${feednewsOrigin}/api/feed/items`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                url: urlInput.value.trim(),
                title: titleInput.value.trim(),
                description: descriptionInput.value.trim(),
            }),
        });

        if (response.status === 401 || response.status === 403) {
            showAuthRequired();
            return;
        }

        if (!response.ok) {
            showSaveError(await responseError(response));
            return;
        }

        form.hidden = true;
        authNotice.hidden = true;
        success.hidden = false;
        window.setTimeout(() => window.close(), 1050);
    } catch {
        showSaveError('Feednews could not be reached. Check your connection and try again.');
    } finally {
        if (success.hidden) {
            setSaving(false);
        }
    }
}

async function openLogin() {
    await chrome.tabs.create({ url: `${feednewsOrigin}/` });
    window.close();
}

async function initialize() {
    try {
        await loadConfiguration();
        await Promise.all([loadCurrentPage(), loadSession()]);
    } catch (error) {
        showPageError(error instanceof Error ? error.message : 'The Feednews extension could not be initialized.');
        saveButton.disabled = true;
    }
}

form.addEventListener('submit', saveItem);
loginButton.addEventListener('click', openLogin);
initialize();
