# Phase 251: SEO Extension Session Export

## Summary
- Added export support for the filtered SEO extension-session workspace.
- Export can include the currently selected session detail snapshot.

## API
- `seo.extension.sessions.export`
  - Accepts the same filters as `seo.extension.sessions.list`
  - Accepts `session_id`
  - Accepts `include_detail`

## UI
- Added `Download Sessions`
- Export respects the active session filters
- If a session detail ID is provided, the export includes that detail payload

## Notes
- This completes the operational workspace pass for SEO extension sessions.
