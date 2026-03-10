# Phase 176: Leads Export

## What changed
- Added `leads.export` for filtered approved-lead exports.
- Added a Leads export button in the app workspace.
- Reused the current Leads filters so export matches the on-screen dataset.

## Why it matters
- Operators can now take the filtered approved-lead set out of the app without leaving the Leads workspace.
- Export uses the same filter logic as the list view, reducing mismatch risk.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
