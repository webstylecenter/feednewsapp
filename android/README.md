# FeedNews Android

Small Android shell around `https://feednews.petervdam.nl`.

## Behaviour

- `MainActivity` opens FeedNews in a WebView with JavaScript, DOM storage and cookies enabled.
- The WebView session cookie is reused by the native share flow.
- `ShareActivity` registers as an Android `ACTION_SEND` target for `text/plain` content.
- Shared URLs are enriched through `POST /api/feed/metadata` and saved through `POST /api/feed/items`.
- Before mutating requests, the app fetches `/api/auth/session` and uses the returned CSRF token.
- No password, API token or long-lived FeedNews credential is stored by the app.

## Build

The project requires JDK 17, Android SDK 35 and Gradle 8.9.

```bash
gradle -p android :app:assembleDebug
```

The APK is generated at:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

GitHub Actions also builds the debug APK and publishes it as a workflow artifact for pull requests that touch the Android project.

## Authentication

Open the FeedNews app and sign in before using the Android share target. The share target deliberately uses the same WebView cookie jar rather than maintaining a second credential.

Email/password login is the most predictable login method in an embedded WebView. OAuth providers can restrict embedded user agents; if a provider rejects WebView authentication, use the regular FeedNews login instead.
