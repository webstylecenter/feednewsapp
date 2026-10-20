# FeedNews browser renderer

Small localhost-only Playwright service used by the FeedNews reader when a website blocks ordinary server-side HTTP requests.

The Chromium process stays running. Every render request gets an isolated browser context which is closed after the HTML has been returned.

## Install

```bash
cd browser-renderer
npm install
npx playwright install --with-deps chromium
```

## Run manually

```bash
npm start
```

The service listens on `127.0.0.1:3010` by default.

Health check:

```bash
curl http://127.0.0.1:3010/health
```

Render a page:

```bash
curl -sS \
    -H 'Content-Type: application/json' \
    -d '{"url":"https://example.com/"}' \
    http://127.0.0.1:3010/render
```

## Symfony configuration

The backend defaults to:

```dotenv
READER_RENDERER_URL=http://127.0.0.1:3010/render
READER_BROWSER_HOSTS=tweakers.net
```

`READER_BROWSER_HOSTS` is a comma-separated list. These hosts are rendered through Chromium before trying the ordinary HTTP reader. Other sites use the normal fast reader first and only fall back to Chromium for blocked/error responses.

## systemd

Example for the current FeedNews production layout:

```ini
[Unit]
Description=FeedNews Chromium renderer
After=network.target

[Service]
Type=simple
User=peter
WorkingDirectory=/home/peter/domains/feednews.me/browser-renderer
Environment=NODE_ENV=production
Environment=PORT=3010
ExecStart=/usr/bin/npm start
Restart=always
RestartSec=3
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

The renderer must not be exposed publicly. It binds to `127.0.0.1` in the application itself.

## Security

The Symfony backend validates article URLs before calling the renderer. The renderer repeats the network check itself and blocks localhost, private IP ranges and reserved network ranges for navigations and subresources.
