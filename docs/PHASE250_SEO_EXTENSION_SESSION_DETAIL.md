# Phase 250: SEO Extension Session Detail

## Summary
- Added a dedicated SEO extension-session detail endpoint and UI panel.
- Detail includes project linkage and recent event usage for the session.

## API
- `seo.extension.sessions.detail`
  - Accepts optional `session_id`
  - Falls back to the latest session when no ID is supplied

## UI
- Added `Session Detail ID`
- Added `Load Session Detail`
- Added a dedicated session detail panel

## Notes
- Masking is preserved while still exposing the session’s operational context.
