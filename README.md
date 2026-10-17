# FeedNews reader changes

After copying these files into the project, install the two new frontend dependencies:

```bash
ddev exec -s frontend npm install
```

Then restart the frontend/DDEV if needed.

The new endpoint is `GET /api/feed/reader?url=...` and is handled through API Platform's `ReaderProvider`.
