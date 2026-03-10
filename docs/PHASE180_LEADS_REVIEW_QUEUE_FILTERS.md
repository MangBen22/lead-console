# Phase 180: Leads Review Queue Filters

## What changed
- Added review queue filtering by review status and site.
- Added queue pagination inputs for page and limit.
- Updated app-side review queue loading to use the new filters.

## Why it matters
- The review queue can now scale beyond a single small pending list.
- Operators can isolate review work by site and status before loading detail.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
