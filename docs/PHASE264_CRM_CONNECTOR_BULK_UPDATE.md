# Phase 264: CRM Connector Bulk Update

## Summary
- Added bulk update support for the filtered CRM connector workspace.

## API
- `crm.connectors.bulk_update`
  - Uses the current connector filters and page scope
  - Supports:
    - `bulk_status`
    - `bulk_site_id`
    - `bulk_run_mode`

## UI
- Added CRM connector bulk update controls.
- Added `Bulk Update Connectors`.

## Notes
- Bulk updates apply to the current filtered connector page, matching the pattern used in Social.
