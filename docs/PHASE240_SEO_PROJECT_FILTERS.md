# Phase 240: SEO Project Filters

## Summary
- Added filtered and paginated SEO project inventory.
- Added status and free-text search controls in the app.
- Updated the SEO project list endpoint to return summary metadata.

## API
- `seo.projects.list`
  - Supports `status`, `search`, `page`, and `limit`.
  - Returns `summary` with total count, filtered count, and status counts.

## UI
- Added SEO project filters for:
  - status
  - search
  - page
  - limit
- Added `Refresh SEO Projects` action.

## Notes
- This phase keeps the existing save/delete/audit flow intact.
- It establishes the inventory pattern needed for later SEO detail and export phases.
