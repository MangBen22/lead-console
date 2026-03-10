# Phase 202: Social Schedule Detail

## Summary
- added Social schedule detail lookup
- returns one schedule item plus simple due-state metadata
- auto-fills the schedule editor form from loaded schedule detail

## App API
- added `social.schedule.detail`
- requires `schedule_id`

## UI
- added a schedule detail form and output panel
- loading schedule detail updates:
  - schedule id
  - title
  - message
  - url
  - connector ids
  - scheduled for

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
