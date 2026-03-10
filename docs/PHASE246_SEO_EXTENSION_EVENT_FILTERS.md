# Phase 246: SEO Extension Event Filters

## Summary
- Added filtered and paginated SEO extension event inventory.
- Extension-event listing now behaves like the other module workspaces instead of returning an unbounded raw feed.

## API
- `seo.extension.events.list`
  - Supports:
    - `project_id`
    - `session_id`
    - `search`
    - `min_score`
    - `max_score`
    - `page`
    - `limit`

## UI
- Added extension-event filters for project, session, search, score range, page, and limit.
- Added `Refresh Extension Events`.

## Notes
- This sets up extension-event detail and export without changing intake behavior.
