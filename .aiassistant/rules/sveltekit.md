---
apply: always
---

You are generating SvelteKit code. Follow these rules strictly.

HARD REQUIREMENTS
- Output must be valid TypeScript (preferred) and SvelteKit-compatible.
- No unsafe any types; keep types explicit.
- Do not invent API routes/contracts; if missing, ask for details.

SVELTEKIT BASICS
- Use the correct SvelteKit primitives:
    - +page.svelte for UI
    - +page.ts / +page.server.ts for load functions
    - +server.ts for endpoints
    - hooks.server.ts for auth/session
- Respect SSR by default:
    - No direct browser-only APIs (window, document, localStorage) in server code.
    - Guard browser-only usage with `browser` from `$app/environment`.

DATA FETCHING & SECURITY
- Never call internal/private APIs from the browser if it must be protected:
    - Use +page.server.ts or +server.ts to proxy with server-side auth.
- Use `fetch` provided by SvelteKit in load functions (not global fetch directly).
- Use cookies securely:
    - httpOnly, secure, sameSite where applicable.
    - Do not store secrets/tokens in localStorage.
- CSRF:
    - For cookie-based sessions, ensure CSRF protection (sameSite + token strategy if needed).

FORMS & ACTIONS
- Prefer SvelteKit form actions for mutations:
    - Use `actions` in +page.server.ts
    - Validate server-side; return structured errors.
- Use progressive enhancement where relevant.

CACHING & PERFORMANCE
- Be explicit about caching:
    - Set `Cache-Control` headers in +server.ts when safe.
    - Use `depends()` and `invalidate()` appropriately.
- Avoid over-fetching in +layout loads; keep data boundaries clear.

ERROR HANDLING
- Use SvelteKit error utilities:
    - `error(status, message)` for fatal errors
    - `fail(status, data)` for form validation failures
- Do not leak internal stack traces/messages to the client.

STATE MANAGEMENT
- Prefer local component state + derived stores.
- Use `load` return values as the primary data source.
- Keep stores serializable across SSR boundary if used in load context.

CODE STYLE
- Use consistent naming, explicit return types where helpful.
- Keep components small; extract reusable logic into lib modules.
- Avoid side effects in module scope (runs on server too).

OUTPUT FORMAT
- Provide complete file content per file requested.
- Do not include explanations unless explicitly requested.