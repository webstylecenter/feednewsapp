package nl.petervdam.feednews;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public final class ShareActivity extends Activity {
    private static final Pattern URL_PATTERN = Pattern.compile("https?://\\S+", Pattern.CASE_INSENSITIVE);

    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private final FeedNewsClient client = new FeedNewsClient();

    private EditText urlInput;
    private EditText titleInput;
    private EditText descriptionInput;
    private TextView statusText;
    private Button saveButton;
    private Button openFeedNewsButton;
    private String csrfToken = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_share);

        View root = findViewById(R.id.share_root);
        root.setOnApplyWindowInsetsListener((view, insets) -> {
            view.setPadding(
                insets.getSystemWindowInsetLeft(),
                insets.getSystemWindowInsetTop(),
                insets.getSystemWindowInsetRight(),
                insets.getSystemWindowInsetBottom()
            );
            return insets;
        });

        urlInput = findViewById(R.id.url);
        titleInput = findViewById(R.id.title);
        descriptionInput = findViewById(R.id.description);
        statusText = findViewById(R.id.status);
        saveButton = findViewById(R.id.save);
        openFeedNewsButton = findViewById(R.id.open_feednews);
        Button cancelButton = findViewById(R.id.cancel);

        cancelButton.setOnClickListener(view -> finish());
        openFeedNewsButton.setOnClickListener(view -> openFeedNews());
        saveButton.setOnClickListener(view -> saveItem());
        saveButton.setEnabled(false);

        importSharedText(getIntent());
        initializeSession();
    }

    private void importSharedText(Intent intent) {
        if (!Intent.ACTION_SEND.equals(intent.getAction())) {
            showStatus("No shared link was provided.");
            return;
        }

        String sharedText = value(intent.getStringExtra(Intent.EXTRA_TEXT));
        String sharedTitle = value(intent.getStringExtra(Intent.EXTRA_SUBJECT));
        Matcher matcher = URL_PATTERN.matcher(sharedText);

        if (matcher.find()) {
            String url = trimTrailingPunctuation(matcher.group());
            urlInput.setText(url);
            titleInput.setText(sharedTitle);

            String description = (sharedText.substring(0, matcher.start()) + sharedText.substring(matcher.end())).trim();
            if (!description.equals(sharedTitle)) {
                descriptionInput.setText(description);
            }
            return;
        }

        showStatus("The shared content does not contain an HTTP or HTTPS URL.");
    }

    private void initializeSession() {
        final String sharedUrl = urlInput.getText().toString().trim();
        showStatus("Checking your FeedNews session…");
        executor.execute(() -> {
            try {
                FeedNewsClient.Session session = client.fetchSession();
                if (!session.isAuthenticated() || session.getCsrfToken().trim().isEmpty()) {
                    runOnUiThread(this::showLoginRequired);
                    return;
                }

                csrfToken = session.getCsrfToken();
                runOnUiThread(() -> {
                    saveButton.setEnabled(true);
                    showStatus("Loading page details…");
                });
                loadMetadata(sharedUrl);
            } catch (Exception exception) {
                runOnUiThread(() -> {
                    showStatus(message(exception));
                    openFeedNewsButton.setVisibility(View.VISIBLE);
                });
            }
        });
    }

    private void loadMetadata(String url) {
        if (url.trim().isEmpty()) {
            runOnUiThread(() -> showStatus("Enter a URL to save."));
            return;
        }

        try {
            FeedNewsClient.Metadata metadata = client.fetchMetadata(csrfToken, url);
            runOnUiThread(() -> {
                if (titleInput.getText().toString().trim().isEmpty() && !metadata.getTitle().trim().isEmpty()) {
                    titleInput.setText(metadata.getTitle());
                }
                if (
                    descriptionInput.getText().toString().trim().isEmpty()
                    && !metadata.getDescription().trim().isEmpty()
                ) {
                    descriptionInput.setText(metadata.getDescription());
                }
                hideStatus();
            });
        } catch (Exception exception) {
            runOnUiThread(() -> showStatus("Page details could not be loaded. You can still edit and save the item."));
        }
    }

    private void saveItem() {
        String url = urlInput.getText().toString().trim();
        String title = titleInput.getText().toString().trim();
        String description = descriptionInput.getText().toString().trim();

        if (!url.startsWith("http://") && !url.startsWith("https://")) {
            showStatus("Enter a valid HTTP or HTTPS URL.");
            return;
        }
        if (csrfToken.trim().isEmpty()) {
            showLoginRequired();
            return;
        }

        saveButton.setEnabled(false);
        showStatus("Saving…");
        executor.execute(() -> {
            try {
                client.createItem(csrfToken, url, title, description);
                runOnUiThread(() -> {
                    Toast.makeText(this, "Saved to FeedNews.", Toast.LENGTH_SHORT).show();
                    finish();
                });
            } catch (FeedNewsClient.ApiException exception) {
                runOnUiThread(() -> {
                    if (exception.getStatus() == 401 || exception.getStatus() == 403) {
                        csrfToken = "";
                        showLoginRequired();
                        return;
                    }
                    saveButton.setEnabled(true);
                    showStatus(message(exception));
                });
            } catch (Exception exception) {
                runOnUiThread(() -> {
                    saveButton.setEnabled(true);
                    showStatus(message(exception));
                });
            }
        });
    }

    private void showLoginRequired() {
        saveButton.setEnabled(false);
        openFeedNewsButton.setVisibility(View.VISIBLE);
        showStatus("Sign in to FeedNews in the app first, then share the link again.");
    }

    private void openFeedNews() {
        Intent intent = new Intent(this, MainActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
        startActivity(intent);
        finish();
    }

    private void showStatus(String message) {
        statusText.setText(message);
        statusText.setVisibility(View.VISIBLE);
    }

    private void hideStatus() {
        statusText.setText("");
        statusText.setVisibility(View.GONE);
    }

    private static String value(String value) {
        return value == null ? "" : value.trim();
    }

    private static String trimTrailingPunctuation(String url) {
        String trimmed = url;
        while (!trimmed.isEmpty() && ".,;:!?)>]".indexOf(trimmed.charAt(trimmed.length() - 1)) >= 0) {
            trimmed = trimmed.substring(0, trimmed.length() - 1);
        }
        return trimmed;
    }

    private static String message(Exception exception) {
        String message = exception.getMessage();
        return message == null || message.trim().isEmpty()
            ? "FeedNews could not be reached. Check your connection and try again."
            : message;
    }

    @Override
    protected void onDestroy() {
        executor.shutdownNow();
        super.onDestroy();
    }
}
