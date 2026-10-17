# FeedNews browser extension

Manifest V3 extension for Chromium-based browsers.

It provides two functions:

1. Click the toolbar action, or use **Save to FeedNews** on a link, to open that URL in the FeedNews import flow.
2. For subframes initiated by `feednews.me` (including `www.feednews.me`) or the DDEV hostname, remove `X-Frame-Options` and `Content-Security-Policy` response headers so the legacy split-screen reader can display sites that opt out of framing.

The header rule is restricted to FeedNews-initiated subframes instead of modifying responses globally as the old Manifest V2 extension did.

If FeedNews is hosted on another domain, add that domain to `rules.json` and change `feedNewsOrigin` in `background.js` before packaging the extension.
