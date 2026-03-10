# Phase 204: Social Schedule Bulk Update

## Summary
- added bulk updates for the current filtered Social schedule page
- supports bulk status and reschedule changes
- refreshes the schedule queue, schedule summary, activity feed, and social module summary after execution

## App API
- added `social.schedule.bulk_update`

## UI
- added a bulk schedule update form
- added `Update Schedule Page`

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
