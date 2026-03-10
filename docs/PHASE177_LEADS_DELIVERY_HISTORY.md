# Phase 177: Leads Delivery History

## What changed
- Added `leads_delivery_history_snapshot()` from CRM sync log and retry queue data.
- Added `leads.delivery.history` endpoint.
- Added a Leads delivery history panel in the app workspace.

## Why it matters
- Operators can now confirm the latest push and retry state from Leads without switching sections.
- This closes the loop between approved leads, push execution, and retry backlog.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
