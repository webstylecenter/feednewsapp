---
apply: always
---

You are generating PHP code for a project with the following strict requirements:

GENERAL
- Target PHP 8.3+ syntax.
- Code must pass PHPStan at level 10 with no errors.
- Code must follow PSR-12 and PHP_CodeSniffer standards.
- Code must pass Psalm (with SymfonyPsalmPlugin)
- Do not use mixed types or untyped arrays.
- Do not suppress errors with @ or phpstan-ignore.
- Do not use shorthand variable names (no $qb, $i, $tmp). Use descriptive variable names.

TYPES & SAFETY
- All method parameters and return types must be strictly typed.
- All properties must have native types.
- No nullable returns unless explicitly required.
- Use value objects or DTOs instead of associative arrays when possible.
- Always handle null explicitly.
- No implicit casting.
- Avoid dynamic properties.
- Avoid magic methods unless explicitly requested.

ARRAYS & COLLECTIONS
- All arrays must be typed in PHPDoc (e.g. array<string, int>).
- Prefer typed objects over arrays.
- Do not return raw arrays from service classes without a defined structure.

EXCEPTIONS & ERRORS
- Throw specific exceptions, never generic Exception.
- Do not swallow exceptions.
- Validate input explicitly.

SYMFONY / DOCTRINE
- Use fully typed repositories and QueryBuilder.
- Do not use SELECT *.
- Avoid partial objects.
- Use explicit joins and aliases.
- Do not use deprecated Doctrine features.

STYLE
- Follow PSR-12 formatting.
- One class per file.
- No unused imports.
- No unused variables.
- Prefer early returns.
- Use strict comparisons (===).

DOCUMENTATION
- Add PHPDoc for complex arrays and DTOs.
- Document generic array shapes where needed.

OUTPUT FORMAT
- Only output valid PHP code unless explanation is explicitly requested.

If a solution cannot be written in a way that passes PHPStan level 10, respond that more information is required instead of guessing.
