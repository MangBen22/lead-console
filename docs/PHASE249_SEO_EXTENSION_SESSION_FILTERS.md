# Phase 249: SEO Extension Session Filters

## Summary
- Added filtered and paginated SEO extension-session inventory.
- Session listing now supports project, status, and text filtering.

## API
- `seo.extension.sessions.list`
  - Supports:
    - `project_id`
    - `status`
    - `search`
    - `page`
    - `limit`

## UI
- Added extension-session filters for project, status, search, page, and limit.
- Added `Refresh Sessions`.

## Notes
- Session masking remains intact.
