# Phase 263: CRM Connector Export

## Summary
- Added export support for the filtered CRM connector workspace.
- Export can include the currently selected connector detail snapshot.

## API
- `crm.connectors.export`
  - Accepts the same filters as `crm.connectors.list`
  - Accepts `connector_id`
  - Accepts `detail_limit`
  - Accepts `include_detail`

## UI
- Added `Download Connector Export`.
- Export respects the active CRM connector filters.
