# Phase 201: Social Schedule Filters

## Summary
- added filter and pagination support to the Social schedule queue
- exposed schedule filters in the app UI
- changed the Social schedule list endpoint to return summary metadata

## Filters
- status
- connector id
- search
- page
- limit

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
