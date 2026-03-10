# Phase 172: Leads Inventory Workspace

## What changed
- Added `leads_inventory_snapshot()` for site, status, category, city, and recent-lead rollups.
- Added `leads_list_snapshot()` for filtered approved lead browsing.
- Added `leads.inventory` and `leads.list` endpoints.
- Added a Leads Inventory workspace in the app UI with search, site, status, and limit controls.

## Why it matters
- The app now has a real Leads view instead of only a module summary card.
- Operators can inspect approved lead volume and browse actual records from connected bridge sites.
- This gives the Leads module a usable operator surface while deeper review/save workflows are still being built.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
