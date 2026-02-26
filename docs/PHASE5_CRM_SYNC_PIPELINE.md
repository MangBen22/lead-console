# Phase 5 CRM Sync Pipeline + API Security

## Delivered
- Added authenticated API guard for app endpoints (session required; `status` stays public).
- Added owner-only write protection for mutating CRM actions.
- Added CSRF token enforcement for write actions.
- Added connector CRUD actions:
  - `crm.connectors.list`
  - `crm.connectors.save` (POST)
  - `crm.connectors.delete` (POST)
- Added CRM push action:
  - `crm.push.sync` (POST)
- Added CRM sync log endpoint:
  - `crm.push.log`

## UI Additions
- Connector form in dashboard to create/update connector records.
- CRM sync action button to run push pipeline.
- Sync result and sync log panels.

## Pipeline Behavior (Current)
- Reads active connectors from local storage.
- Pulls approved leads from each configured WordPress plugin site through bridge endpoint.
- Creates a sync log record per run for audit trail.

## Next
1. Implement provider-specific connector adapters (FluentCRM, HubSpot, etc.).
2. Replace local JSON storage with database tables.
3. Add retry queue and failure classification.
