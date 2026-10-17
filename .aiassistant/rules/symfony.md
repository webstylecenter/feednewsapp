---
apply: always
---

# FeedNews Symfony backend

- Target PHP 8.5 and Symfony 7.4 LTS.
- Keep PHPStan at level 10 and PHPCS/PSR-12/Slevomat clean.
- Prefer final/readonly services, constructor injection, strict types and explicit return types.
- Keep controllers thin; put feed parsing, HTTP/security and domain behavior in services.
- Use Doctrine repositories with typed return PHPDoc for collections.
- Derive the current user from Symfony Security; never trust a user id supplied by the client.
- Browser authentication is stateful Symfony Security with secure session cookies plus CSRF protection for unsafe `/api/*` requests.
- Registration and OAuth are intentionally separate from API Platform. API Platform user resources are admin read-only.
- Validate all external URLs through `PublicUrlGuard`/`NativeHttpClient`; do not use raw `file_get_contents()` or unguarded redirects for user-controlled URLs.
- Do not expose exception traces, secrets, OAuth tokens or database identifiers unnecessarily.
- Prefer attributes over annotations and avoid deprecated Symfony/Doctrine APIs.
