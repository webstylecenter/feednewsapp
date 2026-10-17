# Verification status

This repository was completed from the supplied Laravel and unfinished Symfony repositories.

## Checks performed in the migration environment

- PHP syntax (`php -l`) across all backend PHP files.
- JavaScript syntax (`node --check`) for all JavaScript modules and Vue `<script>` blocks.
- YAML parsing for Symfony configuration.
- JSON parsing for `composer.json` and `package.json`.
- Static comparison of the old Laravel routes/features against the Symfony/Vue implementation.
- Search for remaining Svelte/jQuery application-code references and TODO/FIXME markers.

## Checks that still need an internet-enabled PHP 8.5 environment

The migration environment has PHP 8.4 and no installed Composer/npm dependencies, and package downloads are unavailable there. Therefore the following dependency-aware checks could not be executed reliably during migration:

```bash
ddev start
ddev composer update
ddev exec php backend/bin/console doctrine:migrations:migrate --no-interaction

ddev exec sh -lc 'cd backend && composer quality'
ddev exec -s frontend npm install
ddev exec -s frontend npm run build
```

`composer quality` runs PHPStan level 10, PHP_CodeSniffer (PSR-12 + Slevomat), and PHPUnit.

After `ddev composer update`, commit the generated `backend/composer.lock` before deployment. After `npm install`, commit `frontend/package-lock.json` if npm is the package manager used for deployment.

## Existing Laravel data

Doctrine migrations describe the new Symfony schema. They are not an in-place conversion of an existing Laravel production database. If production data must be retained, test a dedicated data migration against a backup first.
