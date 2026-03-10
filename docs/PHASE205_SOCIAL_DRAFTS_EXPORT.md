# Phase 205: Social Drafts Export

## Summary
- added export support for generated Social drafts
- exports draft preview data together with validation and delivery plan snapshots

## App API
- added `social.drafts.export`

## UI
- added `Download Draft Export` in the Social Draft Preview section

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
