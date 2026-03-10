# Phase 262: CRM Connector Detail

## Summary
- Added a dedicated CRM connector detail endpoint and UI panel.
- Detail includes recent sync entries and retry queue context for the selected connector.

## API
- `crm.connectors.detail`
  - Accepts `connector_id`
  - Accepts `limit`

## UI
- Added connector detail ID and detail limit inputs.
- Added `Load Connector Detail`.
- Loading connector detail also backfills the CRM connector edit form.
