# Phase 268: CRM Retry Filters

## Summary
- Added filtered and paginated CRM retry queue inventory.

## API
- `crm.retry.list`
  - Supports:
    - `status`
    - `connector_id`
    - `provider`
    - `error_code`
    - `search`
    - `page`
    - `limit`

## UI
- Added CRM retry queue filters and a dedicated refresh action.
