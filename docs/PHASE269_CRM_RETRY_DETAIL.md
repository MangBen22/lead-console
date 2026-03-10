# Phase 269: CRM Retry Detail

## Summary
- Added a dedicated CRM retry detail view tied to the filtered retry queue.

## API
- `crm.retry.detail`
  - Inputs:
    - `retry_id`
    - `limit`
  - Returns:
    - retry item
    - masked connector context
    - recent sync items for the same connector

## UI
- Added CRM retry detail controls:
  - retry ID
  - detail limit
  - load detail action
- Loading retry detail backfills the related connector IDs into CRM delivery and connector workspaces.
