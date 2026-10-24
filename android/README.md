# FeedNews Android

Small Android shell around `https://feednews.petervdam.nl`.

## Behaviour

- `MainActivity` opens FeedNews in a WebView with JavaScript, DOM storage and cookies enabled.
- Main-frame WebView navigation is restricted to `https://feednews.petervdam.nl`; other web URLs are opened by Android instead of being rendered inside the JavaScript-enabled WebView.
- The WebView session cookie is reused by the native share flow.
- `ShareActivity` registers as an Android `ACTION_SEND` target for `text/plain` content.
- Shared URLs are enriched through `POST /api/feed/metadata` and saved through `POST /api/feed/items`.
- Before mutating requests, the app fetches `/api/auth/session` and uses the returned CSRF token.
- No password, API token or long-lived FeedNews credential is stored by the app.

## Network security

The application disables cleartext traffic and uses Android Network Security Configuration for `feednews.petervdam.nl`.

The configured SPKI pins are intentionally CA-root pins rather than the short-lived FeedNews leaf certificate:

- ISRG Root X2 is the current Let's Encrypt ECDSA trust path used by FeedNews.
- ISRG Root X1 is the backup pin for a possible Let's Encrypt RSA trust path.
- The pin set expires on `2028-10-25` to avoid permanently breaking older app versions if the server's TLS infrastructure changes later.

JavaScript remains enabled because the FeedNews frontend requires it. The WebView therefore only permits the trusted FeedNews HTTPS origin as a main-frame destination. External pages are delegated to Android instead of running inside the privileged WebView context.

Before changing certificate authorities or before the pin-set expiration date, update `app/src/main/res/xml/network_security_config.xml` and ship a new app version first.

To inspect the currently served certificate chain and derive an SPKI SHA-256 pin:

```bash
echo | openssl s_client \
    -connect feednews.petervdam.nl:443 \
    -servername feednews.petervdam.nl \
    -showcerts
```

For an individual PEM certificate, derive the pin with:

```bash
openssl x509 -in certificate.pem -pubkey -noout \
    | openssl pkey -pubin -outform der \
    | openssl dgst -sha256 -binary \
    | openssl base64 -A
```

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
