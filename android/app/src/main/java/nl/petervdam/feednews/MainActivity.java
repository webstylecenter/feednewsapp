package nl.petervdam.feednews;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;

import java.net.URISyntaxException;
import java.util.Locale;

public final class MainActivity extends Activity {
    private static final String FEEDNEWS_URL = "https://feednews.petervdam.nl";
    private static final String FEEDNEWS_HOST = "feednews.petervdam.nl";

    private WebView webView;
    private ProgressBar progressBar;

    @Override
    @SuppressLint("SetJavaScriptEnabled")
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        View root = findViewById(R.id.main_root);
        root.setOnApplyWindowInsetsListener((view, insets) -> {
            view.setPadding(
                insets.getSystemWindowInsetLeft(),
                insets.getSystemWindowInsetTop(),
                insets.getSystemWindowInsetRight(),
                insets.getSystemWindowInsetBottom()
            );
            return insets;
        });

        webView = findViewById(R.id.web_view);
        progressBar = findViewById(R.id.progress);

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setAcceptThirdPartyCookies(webView, true);

        WebSettings settings = webView.getSettings();
        // FeedNews is a JavaScript application. Main-frame navigation is restricted below to the
        // single trusted HTTPS FeedNews origin; untrusted pages are never loaded with JavaScript here.
        // codeql[java/android/websettings-javascript-enabled]
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setAllowFileAccess(false);
        settings.setAllowContentAccess(false);
        settings.setAllowFileAccessFromFileURLs(false);
        settings.setAllowUniversalAccessFromFileURLs(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setSupportMultipleWindows(false);
        settings.setMediaPlaybackRequiresUserGesture(true);
        settings.setUserAgentString(settings.getUserAgentString() + " FeedNewsAndroid/1.0");

        WebView.setWebContentsDebuggingEnabled(
            (getApplicationInfo().flags & android.content.pm.ApplicationInfo.FLAG_DEBUGGABLE) != 0
        );
        WebView.startSafeBrowsing(this, null);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                if (!request.isForMainFrame()) {
                    return false;
                }

                return handleNavigation(request.getUrl(), request.hasGesture());
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
                progressBar.setVisibility(newProgress >= 100 ? View.GONE : View.VISIBLE);
            }
        });

        webView.setDownloadListener(
            (url, userAgent, contentDisposition, mimeType, contentLength) -> openExternal(Uri.parse(url))
        );

        if (savedInstanceState == null || webView.restoreState(savedInstanceState) == null) {
            webView.loadUrl(FEEDNEWS_URL);
        }
    }

    private boolean handleNavigation(Uri uri, boolean userGesture) {
        String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase(Locale.ROOT);
        String host = uri.getHost();

        if ("https".equals(scheme) && FEEDNEWS_HOST.equalsIgnoreCase(host)) {
            return false;
        }

        if ("http".equals(scheme) || "https".equals(scheme)) {
            openExternal(uri);
            return true;
        }

        if ("intent".equals(scheme)) {
            return userGesture ? openIntentUri(uri.toString()) : true;
        }

        if (!scheme.isEmpty()) {
            if (userGesture) {
                openExternal(uri);
            }
            return true;
        }

        return true;
    }

    private boolean openIntentUri(String uri) {
        try {
            Intent intent = Intent.parseUri(uri, Intent.URI_INTENT_SCHEME);
            intent.addCategory(Intent.CATEGORY_BROWSABLE);
            intent.setComponent(null);
            intent.setSelector(null);

            try {
                startActivity(intent);
                return true;
            } catch (ActivityNotFoundException exception) {
                String fallbackUrl = intent.getStringExtra("browser_fallback_url");
                if (fallbackUrl != null && !fallbackUrl.trim().isEmpty()) {
                    Uri fallbackUri = Uri.parse(fallbackUrl);
                    String fallbackScheme = fallbackUri.getScheme();
                    if ("http".equalsIgnoreCase(fallbackScheme) || "https".equalsIgnoreCase(fallbackScheme)) {
                        openExternal(fallbackUri);
                    }
                }
            }
        } catch (URISyntaxException ignored) {
            return true;
        }

        return true;
    }

    private void openExternal(Uri uri) {
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (ActivityNotFoundException ignored) {
            // Keep FeedNews usable even when Android has no handler for a custom URL scheme.
        }
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        webView.saveState(outState);
        super.onSaveInstanceState(outState);
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
            return;
        }

        super.onBackPressed();
    }

    @Override
    protected void onDestroy() {
        webView.stopLoading();
        webView.destroy();
        super.onDestroy();
    }
}
