# Phase 192: Social Inbox Detail

## Summary
- added Social inbox thread detail lookup
- returns thread data, connector context, and simple thread age metadata
- auto-fills the reply and update forms from loaded thread detail

## App API
- added `social.inbox.detail`
- requires `thread_id`

## UI
- added a thread detail form and output panel
- loading thread detail updates:
  - reply thread id
  - update thread id
  - owner
  - internal note
  - status
  - priority

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
