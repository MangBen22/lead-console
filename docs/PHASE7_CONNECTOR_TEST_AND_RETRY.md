# Phase 7 Connector Test + Retry Queue

## Delivered
- Added connector test endpoint:
  - `crm.connectors.test` (POST, owner + CSRF)
- Added retry queue storage and endpoints:
  - `crm.retry.list`
  - `crm.retry.run` (POST, owner + CSRF)
- Added normalized error codes to connector execution results.
- Failed sync connector runs now enqueue retry records automatically.

## UI Additions
- `Test Connector ID` button
- `Run Retry Queue` button
- Retry queue panel in CRM pipeline section

## Metrics
- `crm.summary.metrics.failed_deliveries` now reflects queued retry items.

## Next
1. Backoff policy and max-attempt lock for retries.
2. Connector-level notification events for repeated failures.
3. Social connector module using same adapter + retry pattern.
