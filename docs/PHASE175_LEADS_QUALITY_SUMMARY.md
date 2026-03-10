# Phase 175: Leads Quality Summary

## What changed
- Added `leads_quality_snapshot()` for completeness and duplicate analysis on approved leads.
- Added `leads.quality` endpoint.
- Added a Leads Quality panel in the app workspace.

## Why it matters
- Operators can now see missing contact fields and duplicate signals before pushing approved leads into CRM connectors.
- The Leads module now exposes data risk, not just volume and delivery readiness.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
