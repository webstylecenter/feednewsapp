import http from 'node:http';
import { lookup } from 'node:dns/promises';
import { mkdir, readFile, rename, writeFile } from 'node:fs/promises';
import { isIP } from 'node:net';
import { dirname } from 'node:path';
import { chromium } from 'playwright';

const host = '127.0.0.1';
const port = Number.parseInt(process.env.PORT || '3010', 10);
const storageStatePath = process.env.STORAGE_STATE_PATH || './storage-state.json';
const headless = process.env.BROWSER_HEADLESS !== '0';
const maxRequestBytes = 16_384;
const maxHtmlBytes = 4_000_000;
const navigationTimeoutMs = 15_000;
const renderDelayMs = 1_500;
const consentTimeoutMs = 8_000;
const browserChallengeTimeoutMs = 10_000;
const dpgPrivacyHost = 'myprivacy.dpgmedia.nl';
const browserChallengeStatusCodes = new Set([403, 429, 503]);
let storageWrite = Promise.resolve();

function isPrivateIpv4(address) {
    const parts = address.split('.').map((part) => Number.parseInt(part, 10));
    if (parts.length !== 4 || parts.some((part) => Number.isNaN(part) || part < 0 || part > 255)) {
        return true;
    }

    const [a, b] = parts;
    return a === 0
        || a === 10
        || a === 127
        || (a === 100 && b >= 64 && b <= 127)
        || (a === 169 && b === 254)
        || (a === 172 && b >= 16 && b <= 31)
        || (a === 192 && b === 168)
        || (a === 198 && (b === 18 || b === 19))
        || a >= 224;
}

function isPrivateIpv6(address) {
    const normalized = address.toLowerCase();
    if (normalized === '::' || normalized === '::1') return true;
    if (normalized.startsWith('fc') || normalized.startsWith('fd')) return true;
    if (/^fe[89ab]/.test(normalized)) return true;

    const ipv4Mapped = /^::ffff:(\d+\.\d+\.\d+\.\d+)$/.exec(normalized);
    return ipv4Mapped ? isPrivateIpv4(ipv4Mapped[1]) : false;
}

function isPrivateIp(address) {
    const family = isIP(address);
    if (family === 4) return isPrivateIpv4(address);
    if (family === 6) return isPrivateIpv6(address);
    return true;
}

async function assertPublicUrl(value, hostCache) {
    let url;
    try {
        url = new URL(value);
    } catch {
        throw new Error('Invalid URL.');
    }

    if (!['http:', 'https:'].includes(url.protocol)) {
        throw new Error('Only HTTP and HTTPS URLs are supported.');
    }
    if (url.username || url.password) {
        throw new Error('URLs containing credentials are not allowed.');
    }

    const hostname = url.hostname.toLowerCase().replace(/\.$/, '');
    if (!hostname || hostname === 'localhost' || hostname.endsWith('.localhost') || hostname.endsWith('.local')) {
        throw new Error('Local URLs are not allowed.');
    }

    if (hostCache.has(hostname)) {
        if (!hostCache.get(hostname)) throw new Error('Private network addresses are not allowed.');
        return;
    }

    if (isIP(hostname)) {
        const allowed = !isPrivateIp(hostname);
        hostCache.set(hostname, allowed);
        if (!allowed) throw new Error('Private network addresses are not allowed.');
        return;
    }

    const addresses = await lookup(hostname, { all: true, verbatim: true });
    const allowed = addresses.length > 0 && addresses.every(({ address }) => !isPrivateIp(address));
    hostCache.set(hostname, allowed);
    if (!allowed) throw new Error('Private network addresses are not allowed.');
}

function readBody(request) {
    return new Promise((resolve, reject) => {
        let body = '';
        request.setEncoding('utf8');
        request.on('data', (chunk) => {
            body += chunk;
            if (Buffer.byteLength(body, 'utf8') > maxRequestBytes) {
                reject(new Error('Request body is too large.'));
                request.destroy();
            }
        });
        request.on('end', () => resolve(body));
        request.on('error', reject);
    });
}

function sendJson(response, statusCode, payload) {
    const body = JSON.stringify(payload);
    response.writeHead(statusCode, {
        'Content-Type': 'application/json; charset=utf-8',
        'Content-Length': Buffer.byteLength(body),
        'Cache-Control': 'no-store',
    });
    response.end(body);
}

function hostnameFromUrl(value) {
    try {
        return new URL(value).hostname.toLowerCase();
    } catch {
        return '';
    }
}

function isHostOrSubdomain(value, rootDomain) {
    const hostname = hostnameFromUrl(value);
    return hostname === rootDomain || hostname.endsWith(`.${rootDomain}`);
}

function isDpgPrivacyGate(value) {
    return hostnameFromUrl(value) === dpgPrivacyHost;
}

async function loadStorageState() {
    try {
        return JSON.parse(await readFile(storageStatePath, 'utf8'));
    } catch (error) {
        if (error && typeof error === 'object' && error.code === 'ENOENT') {
            return undefined;
        }

        console.warn(`Could not load browser storage state: ${error instanceof Error ? error.message : error}`);
        return undefined;
    }
}

async function persistStorageState(context) {
    const state = await context.storageState();
    const body = JSON.stringify(state, null, 2);

    storageWrite = storageWrite
        .catch(() => undefined)
        .then(async () => {
            await mkdir(dirname(storageStatePath), { recursive: true });
            const temporaryPath = `${storageStatePath}.tmp`;
            await writeFile(temporaryPath, body, { encoding: 'utf8', mode: 0o600 });
            await rename(temporaryPath, storageStatePath);
        });

    await storageWrite;
}

async function clickConsentButton(page, consentButtonName, selectors = []) {
    const scopes = [page, ...page.frames().filter((frame) => frame !== page.mainFrame())];

    for (const scope of scopes) {
        for (const selector of selectors) {
            const selectedButton = scope.locator(selector).first();
            try {
                await selectedButton.waitFor({ state: 'visible', timeout: 750 });
                await selectedButton.click();
                return true;
            } catch {
                // Try the next selector.
            }
        }

        const button = scope.getByRole('button', { name: consentButtonName }).first();
        try {
            await button.waitFor({ state: 'visible', timeout: 1_500 });
            await button.click();
            return true;
        } catch {
            // Try the fallback selector or next frame.
        }

        const fallback = scope.locator('button, [role="button"]').filter({ hasText: consentButtonName }).first();
        try {
            await fallback.waitFor({ state: 'visible', timeout: 1_500 });
            await fallback.click();
            return true;
        } catch {
            // Try the next frame.
        }
    }

    return false;
}

async function handleDpgPrivacyGate(page) {
    if (!isDpgPrivacyGate(page.url())) return;

    const consentButtonName = /alles accepteren|alle cookies (?:accepteren|toestaan)|accepteer(?: alles)?|akkoord|ik ga akkoord|toestaan/i;
    const deadline = Date.now() + consentTimeoutMs;
    while (Date.now() < deadline) {
        if (!isDpgPrivacyGate(page.url())) return;

        if (await clickConsentButton(page, consentButtonName)) {
            try {
                await page.waitForURL(
                    (currentUrl) => currentUrl.hostname.toLowerCase() !== dpgPrivacyHost,
                    { timeout: Math.max(1_000, deadline - Date.now()), waitUntil: 'domcontentloaded' },
                );
            } catch {
                // Continue below and verify where the browser ended up.
            }

            if (!isDpgPrivacyGate(page.url())) return;
        }

        await page.waitForTimeout(500);
    }

    throw new Error('DPG privacy gate could not be accepted automatically.');
}

async function handleNeowinConsent(page) {
    if (!isHostOrSubdomain(page.url(), 'neowin.net')) return;

    const consentButtonName = /accept all|accept cookies|allow all|agree|i agree|accept|allow/i;
    const clicked = await clickConsentButton(
        page,
        consentButtonName,
        [
            '#onetrust-accept-btn-handler',
            '[data-testid="accept-all"]',
            '[data-testid="acceptAll"]',
        ],
    );

    if (clicked) {
        await page.waitForTimeout(500);
    }
}

function looksLikeCloudflareChallenge(html) {
    const sample = html.slice(0, 300_000).toLowerCase();
    return [
        'cf-chl-',
        'cf_chl_opt',
        'challenge-platform',
        '<title>just a moment',
        'performing security verification',
        'verify you are human',
    ].some((marker) => sample.includes(marker));
}

async function handleAutomaticBrowserChallenge(page, navigationResponse) {
    if (!navigationResponse || navigationResponse.status() < 400) return;

    const statusCode = navigationResponse.status();
    if (!browserChallengeStatusCodes.has(statusCode)) {
        throw new Error(`Website returned HTTP ${statusCode}.`);
    }

    const initialHtml = await page.content();
    if (!looksLikeCloudflareChallenge(initialHtml)) {
        throw new Error(`Website returned HTTP ${statusCode}.`);
    }

    const deadline = Date.now() + browserChallengeTimeoutMs;
    while (Date.now() < deadline) {
        await page.waitForTimeout(500);
        const html = await page.content();
        if (!looksLikeCloudflareChallenge(html)) {
            await page.waitForLoadState('domcontentloaded').catch(() => undefined);
            return;
        }
    }

    throw new Error(`Website returned HTTP ${statusCode} and the browser challenge did not complete.`);
}

const browser = await chromium.launch({ headless });
const storedState = await loadStorageState();
const contextOptions = {
    locale: 'nl-NL',
    viewport: { width: 1440, height: 1000 },
    extraHTTPHeaders: {
        'Accept-Language': 'nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
        DNT: '1',
    },
};
if (storedState) {
    contextOptions.storageState = storedState;
}
const browserContext = await browser.newContext(contextOptions);

async function renderPage(url) {
    const hostCache = new Map();
    await assertPublicUrl(url, hostCache);

    const page = await browserContext.newPage();
    try {
        await page.route('**/*', async (route) => {
            const requestUrl = route.request().url();
            if (!requestUrl.startsWith('http://') && !requestUrl.startsWith('https://')) {
                await route.continue();
                return;
            }

            try {
                await assertPublicUrl(requestUrl, hostCache);
                await route.continue();
            } catch {
                await route.abort('blockedbyclient');
            }
        });

        const navigationResponse = await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: navigationTimeoutMs,
        });

        await handleAutomaticBrowserChallenge(page, navigationResponse);
        await handleDpgPrivacyGate(page);
        await handleNeowinConsent(page);
        await page.waitForTimeout(renderDelayMs);

        const html = await page.content();
        if (!html.trim()) throw new Error('Browser returned an empty page.');
        if (Buffer.byteLength(html, 'utf8') > maxHtmlBytes) {
            throw new Error('Rendered page is too large.');
        }

        await persistStorageState(browserContext);

        return {
            url: page.url(),
            html,
        };
    } finally {
        await page.close();
    }
}

const server = http.createServer(async (request, response) => {
    if (request.method === 'GET' && request.url === '/health') {
        sendJson(response, 200, { ok: true, headless });
        return;
    }

    if (request.method !== 'POST' || request.url !== '/render') {
        sendJson(response, 404, { error: 'Not found.' });
        return;
    }

    try {
        const body = await readBody(request);
        const payload = JSON.parse(body);
        if (!payload || typeof payload.url !== 'string' || payload.url.trim() === '') {
            sendJson(response, 400, { error: 'URL is required.' });
            return;
        }

        const rendered = await renderPage(payload.url.trim());
        sendJson(response, 200, rendered);
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Rendering failed.';
        sendJson(response, 502, { error: message });
    }
});

server.listen(port, host, () => {
    console.log(`FeedNews browser renderer listening on http://${host}:${port} (headless=${headless})`);
});

async function shutdown() {
    server.close();
    await persistStorageState(browserContext).catch(() => undefined);
    await browserContext.close();
    await browser.close();
    process.exit(0);
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
