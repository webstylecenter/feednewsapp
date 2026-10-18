# Feednews

Feednews is a personal RSS/Atom news reader built with **Symfony 7.4**, **API Platform**, **PHP 8.5**, **Vue 3** and **Vite**.

The application is the modernized successor to the original Laravel Feednews application. The backend exposes DTO-based API Platform resources and the frontend is a Vue single-page application. Public API identifiers use UUIDs; internal Doctrine integer IDs remain persistence details.

## Features

- RSS and Atom feed discovery and subscriptions.
- Automatic feed updates through a Symfony console command.
- Recent items, history and full-text search.
- Per-feed filtering.
- Pinning without reloading the complete feed list.
- Tags with custom colors.
- Manually saved links with metadata extraction.
- Server-side article fetching with a Reader-style frontend.
- YouTube support, including opening links in the YouTube app on mobile.
- Mobile gestures:
  - swipe right-to-left to pin/unpin;
  - swipe left-to-right to open the tag dialog;
  - long-press to open the original article externally;
  - Android/browser back closes the open article before leaving the app;
  - pull-to-refresh reloads newly available items from the database.
- Article descriptions limited to three lines in the feed list.
- Dashboard with:
  - notes;
  - checklist/todos;
  - subscribed feeds;
  - feed management;
  - weather;
  - per-user statistics;
  - items per category;
  - new items per day;
  - top active feeds.
- Optional Google and Facebook OAuth.
- Login throttling.
- CSRF protection for mutating API requests.
- Admin functionality protected by `ROLE_ADMIN`.
- Light, dark and automatic themes.
- Responsive mobile interface.
- Installable PWA with manifest, app icons and service worker.

## Technology

### Backend

- PHP 8.5+
- Symfony 7.4
- API Platform 4
- Doctrine ORM / DBAL
- MariaDB
- PHPUnit
- PHPStan level 10
- PHP_CodeSniffer
- Slevomat Coding Standard

### Frontend

- Vue 3
- Vite
- `@mozilla/readability`
- DOMPurify
- `vite-plugin-pwa`

### Development

- DDEV
- Node.js 22
- MariaDB 11.8

## Repository structure

```text
feednews/
├── .ddev/
├── backend/
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── src/
│   ├── tests/
│   └── composer.json
├── frontend/
│   ├── public/
│   ├── src/
│   ├── package.json
│   └── vite.config.js
└── README.md
```

The Symfony document root is:

```text
backend/public
```

The Vite production build is written directly into `backend/public`.

## Local development with DDEV

Start the project:

```bash
ddev start
```

Install backend dependencies:

```bash
ddev composer install
```

Install frontend dependencies:

```bash
ddev exec -s frontend npm install
```

Run database migrations:

```bash
ddev exec php php backend/bin/console doctrine:migrations:migrate --no-interaction
```

The dedicated DDEV `frontend` service runs the Vite development server automatically.

The local application is normally available at:

```text
https://feednews.ddev.site
```

## Environment configuration

Local Symfony overrides belong in:

```text
backend/.env.local
```

Do not commit secrets.

Typical production/local overrides include:

```dotenv
APP_ENV=prod
APP_SECRET=change-me

DATABASE_URL="mysql://user:password@127.0.0.1:3306/feednews"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://example.com/api/auth/oauth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=https://example.com/api/auth/oauth/facebook/callback

WEATHER_LOCATION=Eindhoven,NL
WEATHER_LATITUDE=51.4416
WEATHER_LONGITUDE=5.4697
```

OAuth redirect URIs must exactly match the callback configured at the provider.

The application routes OAuth callbacks through:

```text
/api/auth/oauth/{provider}/callback
```

## Database

Run migrations after pulling database-related changes:

```bash
ddev exec php php backend/bin/console doctrine:migrations:migrate --no-interaction
```

On production:

```bash
cd /home/peter/domains/feednews.me/backend
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
```

### Legacy Laravel database

Do not run the Symfony migration chain directly against the old Laravel production database as a conversion mechanism.

The project contains a dedicated legacy migration command for migrating data from the old Laravel database into an already migrated Symfony database:

```bash
export LEGACY_DATABASE_URL='mysql://user:password@host/feednews_old'

php bin/console app:migrate-legacy-database --dry-run
php bin/console app:migrate-legacy-database
```

Use a backup and test the migration before applying it to production data.

## Feed and weather updates

Feednews does not require the old Laravel `artisan schedule:work` process.

Feeds are updated with:

```bash
APP_ENV=prod php bin/console feed:update --no-interaction
```

Weather is updated with:

```bash
APP_ENV=prod php bin/console weather:update --no-interaction
```

A production cron configuration can use `flock` to prevent overlapping runs:

```cron
*/5 * * * * flock -n /tmp/feednews-feed-update.lock sh -c 'cd /home/peter/domains/feednews.me/backend && APP_ENV=prod php bin/console feed:update --no-interaction >> var/log/feed-update.log 2>&1'
*/30 * * * * flock -n /tmp/feednews-weather-update.lock sh -c 'cd /home/peter/domains/feednews.me/backend && APP_ENV=prod php bin/console weather:update --no-interaction >> var/log/weather-update.log 2>&1'
```

There is no permanent Symfony Messenger worker required by the current application.

## Frontend build

Install dependencies:

```bash
cd frontend
npm ci
```

Build for production:

```bash
npm run build
```

Vite writes the output to:

```text
backend/public
```

The application uses `vite-plugin-pwa`, which also generates PWA output such as the service worker.

## Generated frontend files

Generated Vite/PWA build files should **not** be committed to Git.

Recommended `.gitignore` entries:

```gitignore
/backend/public/build/
/backend/public/sw.js
/backend/public/workbox-*.js
/backend/public/registerSW.js
/backend/public/manifest.webmanifest
```

This prevents production builds from causing errors such as:

```text
Your local changes would be overwritten by merge
```

If these files were previously committed, remove them from the Git index once from a development checkout:

```bash
git rm -r --cached backend/public/build
git rm --cached backend/public/sw.js
```

Remove any other generated PWA files from the index if they are currently tracked, then commit the `.gitignore` change.

Do not use `git stash` as the normal deployment strategy for generated frontend files.

## PWA

Feednews is installable as a Progressive Web App.

The frontend contains app icons for normal and maskable usage and uses `vite-plugin-pwa` to generate the application manifest and service worker.

Relevant source assets include:

```text
frontend/public/
├── apple-touch-icon.png
├── favicon.ico
├── favicon-16x16.png
├── favicon-32x32.png
└── icons/
    ├── icon-192.png
    ├── icon-512.png
    └── icon-512-maskable.png
```

PWA functionality requires HTTPS in production.

Android browsers can install the application as a standalone web app. On iOS/iPadOS it can be added through Safari's **Add to Home Screen** action.

## Article reader

Normal external articles are not embedded directly in an iframe.

The backend fetches eligible public HTTP(S) pages through:

```text
GET /api/feed/reader?url=...
```

The reader includes SSRF protections:

- only HTTP(S);
- public network destinations only;
- redirects are revalidated;
- response size limits;
- content-type validation;
- TLS verification.

The frontend then extracts the article with Mozilla Readability and sanitizes the resulting HTML using DOMPurify.

YouTube URLs are handled separately.

## Dashboard statistics

User-specific statistics are loaded separately from the main dashboard:

```text
GET /api/dashboard/statistics
```

The statistics include:

- total items;
- unread/opened items;
- pinned items;
- items by category;
- new items over the last 14 days;
- most active feeds over the recent period.

The separate endpoint prevents statistics queries from delaying the normal dashboard response.

Database indexes are used for the user/date aggregate queries.

## API architecture

The backend uses API Platform as the public API layer.

General structure:

```text
DTO Resource
    ↓
Provider / Processor
    ↓
Repository / Service
    ↓
Doctrine Entity
```

Repositories are internal persistence details and should not define the public API contract.

Public references use UUIDs where the entity has a UUID.

Examples:

```text
/api/feed/items/{uuid}/pin
/api/feed/items/{uuid}/opened
/api/feed/tag/{uuid}
/api/tags/{uuid}
/api/notes/{uuid}
/api/checklist/{uuid}
/api/admin/users/{uuid}
/api/users/{uuid}
```

The special feed source identifier:

```text
manual
```

is used for manually saved links.

## Authentication and security

The application uses Symfony session authentication.

Login endpoint:

```text
POST /api/auth/login
```

Relevant security behavior includes:

- session-based authentication;
- CSRF validation for mutating JSON API calls;
- login throttling;
- `ROLE_ADMIN` authorization for admin endpoints;
- OAuth credentials loaded from environment configuration;
- production exception responses hide internal details;
- development/debug responses may expose additional exception information.

## Code quality

Run all backend checks:

```bash
ddev composer quality
```

This runs the configured PHPStan, PHPCS and PHPUnit checks.

They can also be run separately:

```bash
ddev composer phpstan
ddev composer phpcs
ddev composer test
```

Automatic PHPCS fixes where possible:

```bash
ddev composer phpcbf
```

Frontend production validation:

```bash
ddev exec -s frontend npm run build
```

## Production deployment

The production application currently uses:

```text
/home/peter/domains/feednews.me
```

A normal deployment is:

```bash
cd /home/peter/domains/feednews.me

git pull

cd backend
APP_ENV=prod composer install --no-dev --optimize-autoloader
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod php bin/console cache:clear

cd ../frontend
npm ci
npm run build
```

Because generated frontend output is ignored by Git, running `npm run build` on production does not interfere with the next `git pull`.

Depending on permissions, ensure both the deployment user and the webserver can write Symfony's `backend/var` directory.

## Apache production configuration

The Apache document root must point to:

```text
/home/peter/domains/feednews.me/backend/public
```

Example:

```apache
<VirtualHost *:443>
    ServerName feednews.petervdam.nl

    DocumentRoot /home/peter/domains/feednews.me/backend/public

    <Directory /home/peter/domains/feednews.me/backend/public>
        Options FollowSymLinks
        AllowOverride None
        Require all granted
        DirectoryIndex index.php
        FallbackResource /index.php
    </Directory>

    SSLEngine on

    # Certificate configuration...
</VirtualHost>
```

`FallbackResource /index.php` is important so Symfony receives routes such as:

```text
/api/auth/login
/api/feed/items
/api/dashboard/statistics
```

while existing frontend assets continue to be served as static files.

## Production troubleshooting

### Symfony route exists but Apache returns an HTML 404

Check:

```bash
APP_ENV=prod php bin/console debug:router
apache2ctl -S
```

If `curl` returns a raw Apache HTML 404 instead of a JSON/Symfony response, the request is not reaching Symfony. Check the active virtual host, document root and `FallbackResource`.

### OAuth buttons are missing

Verify the environment visible to Symfony:

```bash
APP_ENV=prod php bin/console debug:dotenv
```

If a dumped production environment is used, regenerate it after changing environment variables:

```bash
composer dump-env prod
```

Then clear the Symfony cache.

### Frontend looks outdated after deployment

Rebuild it:

```bash
cd /home/peter/domains/feednews.me/frontend
npm ci
npm run build
```

Then hard-refresh the browser. Because Feednews is a PWA, also consider service-worker caching when testing frontend deployment changes.

## Production URL

Current production deployment:

```text
https://feednews.petervdam.nl
```

## License

Private project. No public license is granted.
