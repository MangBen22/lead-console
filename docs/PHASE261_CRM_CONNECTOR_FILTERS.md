# Phase 261: CRM Connector Filters

## Summary
- Added filtered and paginated CRM connector inventory.
- CRM connector listing now returns summary metadata instead of a raw full dump.

## API
- `crm.connectors.list`
  - Supports:
    - `provider`
    - `status`
    - `site_id`
    - `run_mode`
    - `search`
    - `page`
    - `limit`

## UI
- Added CRM connector filters for provider, status, site, run mode, search, page, and limit.
- Added `Refresh CRM Connectors`.

## Notes
- Connector masking remains unchanged.
