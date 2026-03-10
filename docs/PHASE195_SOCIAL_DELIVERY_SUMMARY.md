# Phase 195: Social Delivery Summary

## Summary
- added a connector-level Social delivery summary
- aggregates social sync log and retry queue data by connector
- surfaces retry debt, rejection totals, latest sync state, and error-code counts

## App API
- added `social.delivery.summary`

## UI
- added `Refresh Delivery Summary` in the Social Push Pipeline section
- added a `Social Delivery Summary` panel ahead of the raw social sync log and retry queue
- Social sync and retry actions now refresh the summary automatically

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
