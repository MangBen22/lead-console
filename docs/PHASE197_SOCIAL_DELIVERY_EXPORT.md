# Phase 197: Social Delivery Export

## Summary
- added export support for Social delivery diagnostics
- exports the Social delivery summary and optionally one connector detail view
- added audit logging for Social delivery exports

## App UI
- added `Download Delivery Export` in the Social Push Pipeline section

## App API
- added `social.delivery.export`

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
