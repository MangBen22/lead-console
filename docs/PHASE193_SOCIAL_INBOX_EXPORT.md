# Phase 193: Social Inbox Export

## Summary
- added export support for the Social inbox
- exports the current filtered inbox page
- optionally includes one thread detail snapshot

## App UI
- added `Download Inbox Export`
- uses the active inbox filters and optional detail thread id

## App API
- added `social.inbox.export`
- returns:
  - export timestamp
  - filter summary
  - filtered thread list
  - optional thread detail

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
