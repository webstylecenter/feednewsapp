# Feednews current-project fixes

This patch is based directly on the supplied `project.zip` and only changes the files required for the four reported issues. Dashboard statistics are untouched.

## Fixes

1. Feed descriptions are clamped to three lines using `-webkit-line-clamp: 3` with a max-height fallback.
2. The "Most active feeds" widget is constrained to the card width on mobile; long feed names use ellipsis without widening the grid.
3. PATCH requests now use `application/merge-patch+json`, matching API Platform's PATCH format and fixing the HTTP 415 response.
4. Feed update/delete lookup binds the feed UUID as Doctrine type `uuid`. The `feed.uuid` column is `BINARY(16)`, so binding the UUID as a plain string could fail to match and lead to a 404.

## Files changed

- `frontend/src/api.js`
- `frontend/src/app.css`
- `backend/config/packages/api_platform.yaml`
- `backend/src/Feed/Repository/UserFeedRepository.php`

## After applying

```bash
ddev exec php php backend/bin/console cache:clear
ddev exec -s frontend npm run build
ddev composer quality
```

No database migration is required.
