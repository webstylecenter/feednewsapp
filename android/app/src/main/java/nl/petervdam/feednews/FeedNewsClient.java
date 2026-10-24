package nl.petervdam.feednews;

import android.webkit.CookieManager;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Map;

final class FeedNewsClient {
    private static final String BASE_URL = "https://feednews.petervdam.nl";
    private static final int CONNECT_TIMEOUT_MS = 10_000;
    private static final int READ_TIMEOUT_MS = 20_000;

    Session fetchSession() throws IOException, JSONException, ApiException {
        JSONObject response = request("GET", "/api/auth/session", null, null);
        JSONObject user = response.optJSONObject("user");
        String csrfToken = response.optString("csrfToken", "");

        return new Session(user != null, csrfToken);
    }

    Metadata fetchMetadata(String csrfToken, String url) throws IOException, JSONException, ApiException {
        JSONObject body = new JSONObject();
        body.put("url", url);

        JSONObject response = request("POST", "/api/feed/metadata", csrfToken, body);
        JSONObject data = response.optJSONObject("data");
        if (data == null) {
            return new Metadata("", "");
        }

        return new Metadata(
            data.optString("title", ""),
            data.optString("description", "")
        );
    }

    void createItem(String csrfToken, String url, String title, String description)
        throws IOException, JSONException, ApiException {
        JSONObject body = new JSONObject();
        body.put("url", url);
        body.put("title", title);
        body.put("description", description);
        request("POST", "/api/feed/items", csrfToken, body);
    }

    private JSONObject request(String method, String path, String csrfToken, JSONObject body)
        throws IOException, JSONException, ApiException {
        HttpURLConnection connection = (HttpURLConnection) new URL(BASE_URL + path).openConnection();
        connection.setConnectTimeout(CONNECT_TIMEOUT_MS);
        connection.setReadTimeout(READ_TIMEOUT_MS);
        connection.setRequestMethod(method);
        connection.setRequestProperty("Accept", "application/json");
        connection.setRequestProperty("User-Agent", "FeedNewsAndroid/1.0");

        String cookies = CookieManager.getInstance().getCookie(BASE_URL);
        if (cookies != null && !cookies.trim().isEmpty()) {
            connection.setRequestProperty("Cookie", cookies);
        }

        if (csrfToken != null && !csrfToken.trim().isEmpty()) {
            connection.setRequestProperty("X-CSRF-TOKEN", csrfToken);
        }

        if (body != null) {
            byte[] bytes = body.toString().getBytes(StandardCharsets.UTF_8);
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8");
            connection.setFixedLengthStreamingMode(bytes.length);
            try (OutputStream output = connection.getOutputStream()) {
                output.write(bytes);
            }
        }

        int status = connection.getResponseCode();
        updateCookies(connection.getHeaderFields());

        InputStream stream = status >= 200 && status < 400
            ? connection.getInputStream()
            : connection.getErrorStream();
        String responseBody = read(stream);
        connection.disconnect();

        if (status < 200 || status >= 300) {
            throw new ApiException(status, errorMessage(status, responseBody));
        }

        if (responseBody.trim().isEmpty()) {
            return new JSONObject();
        }

        return new JSONObject(responseBody);
    }

    private static void updateCookies(Map<String, List<String>> headers) {
        CookieManager cookieManager = CookieManager.getInstance();
        for (Map.Entry<String, List<String>> header : headers.entrySet()) {
            if (header.getKey() == null || !"set-cookie".equalsIgnoreCase(header.getKey())) {
                continue;
            }

            for (String cookie : header.getValue()) {
                cookieManager.setCookie(BASE_URL, cookie);
            }
        }
        cookieManager.flush();
    }

    private static String read(InputStream stream) throws IOException {
        if (stream == null) {
            return "";
        }

        StringBuilder response = new StringBuilder();
        try (BufferedReader reader = new BufferedReader(new InputStreamReader(stream, StandardCharsets.UTF_8))) {
            String line;
            while ((line = reader.readLine()) != null) {
                response.append(line).append('\n');
            }
        }

        return response.toString().trim();
    }

    private static String errorMessage(int status, String responseBody) {
        if (!responseBody.trim().isEmpty()) {
            try {
                JSONObject response = new JSONObject(responseBody);
                String detail = response.optString("detail", "");
                if (!detail.trim().isEmpty()) {
                    return detail;
                }

                String message = response.optString("message", "");
                if (!message.trim().isEmpty()) {
                    return message;
                }
            } catch (JSONException ignored) {
                // Fall back to the HTTP status when the API did not return JSON.
            }
        }

        return "FeedNews returned HTTP " + status + ".";
    }

    static final class Session {
        private final boolean authenticated;
        private final String csrfToken;

        Session(boolean authenticated, String csrfToken) {
            this.authenticated = authenticated;
            this.csrfToken = csrfToken;
        }

        boolean isAuthenticated() {
            return authenticated;
        }

        String getCsrfToken() {
            return csrfToken;
        }
    }

    static final class Metadata {
        private final String title;
        private final String description;

        Metadata(String title, String description) {
            this.title = title;
            this.description = description;
        }

        String getTitle() {
            return title;
        }

        String getDescription() {
            return description;
        }
    }

    static final class ApiException extends Exception {
        private final int status;

        ApiException(int status, String message) {
            super(message);
            this.status = status;
        }

        int getStatus() {
            return status;
        }
    }
}
