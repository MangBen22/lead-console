# Phase 186: CRM Delivery Summary

## Summary
- added a connector-level CRM delivery summary panel
- aggregates CRM sync log and retry queue data by connector
- surfaces retry debt, rejection totals, latest sync state, and error-code counts

## App API
- added `crm.delivery.summary`
- combines:
  - configured CRM connectors
  - `crm_sync_log.json`
  - `crm_retry_queue.json`

## UI
- added `Refresh Delivery Summary` in the CRM Push Pipeline section
- added a `Delivery Summary` panel ahead of the raw sync log and retry queue
- CRM sync and retry actions now refresh the summary automatically

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
