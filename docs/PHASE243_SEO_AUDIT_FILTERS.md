# Phase 243: SEO Audit Filters

## Summary
- Added filtered and paginated SEO audit inventory.
- Audit list now returns summary metadata instead of a raw unbounded dump.

## API
- `seo.audits.list`
  - Supports:
    - `project_id`
    - `source`
    - `search`
    - `min_score`
    - `max_score`
    - `page`
    - `limit`
  - Returns summary data including:
    - average score
    - critical total
    - warning total
    - source counts

## UI
- Added SEO audit filters for project, source, search, score range, page, and limit.
- Added `Refresh SEO Audits`.

## Notes
- This establishes the SEO audit workspace pattern needed for detail and export phases.
