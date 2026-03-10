# Phase 267: CRM Sync Log Export

## Summary
- Added export support for the filtered CRM sync log workspace.
- Export can include the currently selected sync detail payload.

## API
- `crm.push.export`
  - Accepts the same filters as `crm.push.log`
  - Accepts `sync_id`
  - Accepts `include_detail`

## UI
- Added `Download Sync Export`.
