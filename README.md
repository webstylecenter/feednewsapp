# FeedNews

FeedNews is a Symfony 7.4 + Vue 3 application for reading RSS/Atom feeds, saving links, pinning and tagging articles, keeping notes/todos, viewing weather information and sharing saved items.

This repository is the modernized successor to the original Laravel FeedNews application. The backend is Symfony/PHP 8.5 and the browser UI is Vue 3. jQuery, Svelte, Hammer, Handlebars and Spectrum are not required.

## Main functionality

- Account registration, login/logout and optional Google/Facebook OAuth.
- Login throttling: five failed attempts per ten-minute window per IP/e-mail combination.
- RSS/Atom feed discovery, follow/unfollow, update, color/icon configuration and automatic refresh.
- Recent items, opened-item history, search, tags, pinning and manually saved links.
- Metadata extraction for manually saved links.
- Reader iframe with X-Frame/CSP detection, safe external fallback, YouTube embed mode, focus mode and floating reader.
- Public share links using non-sequential UUIDs.
- Browser-extension and Android import flows.
- Notes with autosave and checklist/todo management.
- Weather forecast and Dutch precipitation radar.
- Dashboard, introduction, settings, feedback/privacy/offline pages and screensaver.
- Admin user overview/removal for `ROLE_ADMIN` users.
- Responsive mobile UI without jQuery gesture libraries.

## Requirements

- PHP 8.5+
- Composer 2
- MariaDB 11.8+ (the included DDEV setup uses 11.8)
- Node.js 22+
- npm

DDEV is configured with these runtime versions.

## Local installation with DDEV

```bash
ddev start
ddev composer update
ddev exec php backend/bin/console doctrine:migrations:migrate --no-interaction
ddev exec -s frontend npm install
```

The dedicated `frontend` service starts Vite automatically and DDEV proxies the normal project URL to it. Symfony API/public routes continue to be handled by PHP-FPM.

Copy backend environment overrides to `backend/.env.local` as needed. At minimum configure `DATABASE_URL` outside DDEV when a different database is used. OAuth is optional:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=

WEATHER_LOCATION=Eindhoven,NL
WEATHER_LATITUDE=51.4416
WEATHER_LONGITUDE=5.4697
```

Open-Meteo is used for forecast data and does not require an API key.

## Dependency lock

The stale Composer lock from the unfinished migration was deliberately removed because it pinned Symfony 7.4.5. Run `ddev composer update` once in an internet-enabled environment and commit the newly generated `backend/composer.lock` before deploying. The Symfony requirements remain on the maintained 7.4 LTS branch, while API Platform is constrained to the compatible 4.x series.

## Production frontend build

```bash
cd frontend
npm install
npm run build
```

Vite writes the production bundle and `index.html` to `backend/public`. The Symfony SPA fallback serves that generated file for browser routes.

For production, configure the web server with `backend/public` as the document root and route unknown browser paths through `index.php`; static `/build`, `/images` and `/downloads` assets should be served directly.

## Scheduled feed/weather updates

The original Laravel scheduler behavior maps to two Symfony commands:

```bash
php backend/bin/console feed:update --env=prod
php backend/bin/console weather:update --env=prod
```

Equivalent cron entries are:

```cron
*/10 * * * * cd /path/to/feednews && php backend/bin/console feed:update --env=prod >/dev/null 2>&1
*/30 * * * * cd /path/to/feednews && php backend/bin/console weather:update --env=prod >/dev/null 2>&1
```

## Code quality

Backend quality configuration is included for PHPStan level 10 and PHP_CodeSniffer/PSR-12 + Slevomat. After the initial `composer update` has generated `backend/composer.lock`:

```bash
cd backend
composer phpstan
composer phpcs
composer test
```

Frontend build validation:

```bash
cd frontend
npm run build
```

## Security changes compared with the Laravel version

- User-supplied URLs are restricted to HTTP(S) public addresses before server-side fetching to reduce SSRF exposure.
- HTTP fetching has size/redirect limits and TLS verification.
- Mutating JSON API requests require a session CSRF token.
- Public share links use UUIDs instead of enumerable numeric IDs.
- Admin endpoints require `ROLE_ADMIN`.
- Embedded content runs in a sandboxed iframe where compatible; sites that prohibit framing are opened externally.
- Passwords continue to use Symfony's password hasher and existing supported hashes can be rehashed on login when necessary.

## Legacy database note

The Symfony project uses Doctrine-managed table/entity names and migrations. These migrations create/upgrade the Symfony schema; they are **not** an in-place Laravel-schema conversion script. If an existing production Laravel database must be retained, migrate its data into the new schema from a backup rather than running the Symfony migration chain directly against that database without first testing the conversion.

## Browser extension

`frontend/public/downloads/chrome-extension.zip` contains the migrated Manifest V3 extension. It opens FeedNews with the URL in the new import flow instead of posting to the removed Laravel `/feed/chrome-import/` endpoint.
