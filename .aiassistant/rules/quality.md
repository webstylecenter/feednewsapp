---
apply: always
---

# FeedNews quality rules

- PHP must pass PHPStan level 10 and PHP_CodeSniffer with the repository ruleset.
- Use `declare(strict_types=1);`, explicit native types and PHPDoc only where generics/array shapes are required.
- No error suppression, dynamic properties, implicit user ownership, unsafe unserialize/eval, or raw user-controlled filesystem/network access.
- Keep names descriptive and complexity low; use early returns and strict comparisons.
- Preserve entity relationship invariants on both sides where applicable.
- Frontend code should stay dependency-light and prefer browser APIs over helper libraries.
