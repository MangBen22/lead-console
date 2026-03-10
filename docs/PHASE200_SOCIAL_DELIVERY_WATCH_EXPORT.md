# Phase 200: Social Delivery Watch Export

## Summary
- added export support for Social delivery watch data
- exports latest watch state and recent watch runs
- added audit logging for Social delivery watch exports

## App UI
- added `Download Delivery Watch` in the Social Push Pipeline section

## App API
- added `social.delivery.watch.export`

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
