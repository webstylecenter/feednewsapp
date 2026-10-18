# Feednews PWA files

Copy the contents of this zip into the frontend project root.

## Dependency

```bash
npm install -D vite-plugin-pwa
```

The supplied `vite.config.js` includes the missing `VitePWA` import and generates the web app manifest during the Vite build.

Remove the two old static manifests to avoid conflicting manifests:

```bash
rm -f public/manifest.json public/manifest.webmanifest
```

Make sure these are present inside `<head>` in `index.html`:

```html
<meta name="theme-color" content="#337dff">
<meta name="mobile-web-app-capable" content="yes">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
```

The manifest link and service-worker registration are handled by `vite-plugin-pwa`.

Then build:

```bash
npm install
npm run build
```
