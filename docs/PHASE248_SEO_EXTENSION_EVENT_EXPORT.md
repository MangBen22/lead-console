# Phase 248: SEO Extension Event Export

## Summary
- Added export support for the filtered SEO extension-event workspace.
- Export can include the currently selected event detail snapshot.

## API
- `seo.extension.events.export`
  - Accepts the same filters as `seo.extension.events.list`
  - Accepts `event_id`
  - Accepts `include_detail`

## UI
- Added `Download Extension Events`
- Export respects the current extension-event filters
- If an event detail ID is present, the export includes that event detail payload

## Notes
- This completes the first operational pass for SEO extension-event handling.
