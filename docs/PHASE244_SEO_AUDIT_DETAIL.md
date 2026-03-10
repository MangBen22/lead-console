# Phase 244: SEO Audit Detail

## Summary
- Added a dedicated SEO audit detail endpoint and UI panel.
- Detail includes issue rollup, page signals, project link, and previous-audit comparison.

## API
- `seo.audits.detail`
  - Accepts optional `audit_id`
  - Falls back to the latest audit when no ID is supplied

## UI
- Added `Audit Detail ID`
- Added `Load Audit Detail`
- Added a separate SEO audit detail panel

## Notes
- This makes individual crawl review practical and prepares the SEO audit workspace for export.
