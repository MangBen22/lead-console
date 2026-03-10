# Phase 245: SEO Audit Export

## Summary
- Added export support for the filtered SEO audit workspace.
- Export can include the currently selected audit detail snapshot.

## API
- `seo.audits.export`
  - Accepts the same filters as `seo.audits.list`
  - Accepts `audit_id`
  - Accepts `include_detail`

## UI
- Added `Download SEO Audits`
- Export respects the active audit filters
- If an audit detail ID is provided, the export includes that audit detail payload

## Notes
- This matches the workspace export pattern already used across the other modules.
