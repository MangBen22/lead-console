# Phase 247: SEO Extension Event Detail

## Summary
- Added a dedicated SEO extension-event detail endpoint and UI panel.
- Detail now includes linked project context, normalized page signals, and linked audit lookup.

## API
- `seo.extension.events.detail`
  - Accepts optional `event_id`
  - Falls back to the latest extension event when no ID is supplied

## UI
- Added `Event Detail ID`
- Added `Load Event Detail`
- Added a separate extension-event detail panel

## Notes
- This makes extension evidence review practical instead of forcing operators to inspect full event lists manually.
