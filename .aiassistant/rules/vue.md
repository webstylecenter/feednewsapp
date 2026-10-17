---
apply: always
---

# FeedNews Vue frontend

- Use Vue 3 Composition API and Vite.
- Do not add jQuery, Svelte, global DOM helper libraries or a client router unless a real need appears.
- Prefer native browser features: `fetch`, History API, Clipboard API, Pointer Events, IntersectionObserver, `<dialog>`, semantic HTML and modern CSS.
- Keep JavaScript simple and avoid dependencies for functionality available in maintained browsers.
- All state-changing API calls use the shared `api()` helper so session credentials and CSRF headers are handled consistently.
- Never render remote HTML with `v-html`; treat feed titles/descriptions as text.
- Do not store authentication material in localStorage/sessionStorage.
