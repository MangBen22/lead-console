# Phase 270: CRM Retry Export

## Summary
- Added export coverage for the filtered CRM retry queue with optional retry detail.

## API
- `crm.retry.export`
  - Supports the same filters as `crm.retry.list`
  - Optional:
    - `retry_id`
    - `detail_limit`
  - Returns:
    - retry queue snapshot
    - retry detail payload when requested

## UI
- Added a CRM retry export action beside the retry queue filters.
- Export uses the current retry filters and includes the loaded retry detail when a retry ID is provided.
