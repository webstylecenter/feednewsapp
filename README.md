# FeedNews UUID/mobile/scroll patch

This patch contains only changed files.

## Changes

- Dashboard Add a feed / Available feeds widgets now scroll vertically when their content exceeds the widget height.
- Notes use the existing vertical tabs on desktop and a select box on mobile.
- Public API identifiers have been changed from database primary keys to UUIDs for entities that expose a UUID.
- Feed subscriptions are addressed externally by the UUID of the feed rather than the internal `feed_user.id`.
- Saved/manual links keep the special `manual` source key because it is not a database identifier.
- Notes now use `POST /api/notes` for creation and `PATCH /api/notes/{uuid}` for updates.

## Examples

- `/api/feed/items/{uuid}/pin`
- `/api/feed/items/{uuid}/opened`
- `/api/feed/tag/{uuid}`
- `/api/tags/{uuid}`
- `/api/notes/{uuid}`
- `/api/checklist/{uuid}`
- `/api/settings/feeds/{feedUuid}`
- `/api/admin/users/{uuid}`
- `/api/users/{uuid}`

No database schema migration is required: the relevant UUID columns already exist.

After copying the files, clear the Symfony cache and run the quality checks:

```bash
ddev exec php backend/bin/console cache:clear
ddev composer quality
```
