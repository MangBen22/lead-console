# Phase 184: Leads Review Queue Bulk Action

## Summary
- added bulk save and bulk discard for the current filtered review queue page
- reused the same queue filters already present in the Leads workspace
- kept post-action refreshes aligned with the existing Leads and CRM panels

## App API
- added `leads.review.queue.bulk_action`
- requires owner session, CSRF, and an `action` of `save` or `discard`
- processes the currently filtered queue page returned by `collect_leads_review_queue()`

## UI
- added:
  - `Save Queue Page`
  - `Discard Queue Page`
- bulk action results are written to the review action panel
- after completion the app refreshes:
  - review queue
  - review detail
  - leads inventory
  - leads list
  - leads push plan
  - leads quality
  - Leads and CRM module cards

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
