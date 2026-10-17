# Feednews performance + pinned item changes

Copy the `backend/` and `frontend/` trees over the matching project directories.

Then run:

```bash
ddev exec php backend/bin/console doctrine:migrations:migrate --no-interaction
ddev exec php backend/bin/console cache:clear
ddev composer quality
```

The migration adds composite indexes used by the reader/tag/history queries.

Key behaviour changes:

- Dashboard feed item counts are calculated in SQL instead of initializing Doctrine collections.
- Recent feed items exclude pinned records and fetch all mapper associations in one query.
- `/api/feed/items` returns at most three initial pinned items plus `pinnedCount`.
- `/api/feed/pinned` loads all pinned items only when the user expands them.
- Tag counts are grouped in one query instead of one COUNT query per tag.
- The frontend shows three pins initially and an expand/collapse control when more exist.
