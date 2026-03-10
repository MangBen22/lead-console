# Phase 203: Social Schedule Export

## Summary
- added export support for the Social schedule queue
- exports the current filtered schedule page
- optionally includes one schedule detail snapshot

## App API
- added `social.schedule.export`

## UI
- added `Download Schedule Export`
- uses active schedule filters and optional detail schedule id

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
