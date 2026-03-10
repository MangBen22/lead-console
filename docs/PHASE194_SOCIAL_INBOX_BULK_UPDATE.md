# Phase 194: Social Inbox Bulk Update

## Summary
- added bulk updates for the current filtered Social inbox page
- supports bulk status, priority, owner, and internal note changes
- refreshes inbox summary, workload, watch, activity, and module summary after execution

## App API
- added `social.inbox.bulk_update`
- applies updates to the current filtered inbox page from `social_inbox_list_snapshot()`

## UI
- added a bulk inbox update form
- added `Update Inbox Page`

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
