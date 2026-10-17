# FeedNews frontend

Vue 3 + Vite frontend for FeedNews. Runtime dependencies are intentionally limited to Vue; interaction features use browser-native APIs where practical.

```bash
npm install
npm run dev
npm run build
```

The production build is written to `../backend/public`.

Native browser features replace the legacy frontend dependencies:

- `<dialog>` for modals
- Pointer Events for swipe actions
- `IntersectionObserver` for endless loading
- Clipboard API for share links
- History API for client-side navigation
- native `<input type="color">` for feed/tag colors
- CSS media queries and `color-scheme` for responsive/light/dark presentation

There is no jQuery or Svelte runtime.
