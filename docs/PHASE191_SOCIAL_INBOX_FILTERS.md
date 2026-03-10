# Phase 191: Social Inbox Filters

## Summary
- added filter and pagination support to the Social inbox list
- exposed inbox filters in the app UI
- changed the Social inbox list endpoint to return summary metadata

## Filters
- status
- priority
- provider
- owner
- search
- page
- limit

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
