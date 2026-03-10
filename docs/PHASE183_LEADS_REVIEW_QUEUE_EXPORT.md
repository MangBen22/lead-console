# Phase 183: Leads Review Queue Export

## Summary
- added export support for the filtered Leads review queue
- reused the current queue filters from the app UI
- added audit logging for review queue exports

## App UI
- added `Download Review Queue` beside the review queue refresh action
- the export uses the current:
  - review status
  - site filter
  - page
  - limit

## App API
- added `leads.review.queue.export`
- returns:
  - export timestamp
  - active filters
  - queue summary
  - current filtered queue items

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
