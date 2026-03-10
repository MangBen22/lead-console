# Phase 170: Social Inbox Watch Automation

## What changed
- Added persisted Social inbox watch state and run history.
- Added `social.inbox.watch.summary` and `social.inbox.watch.run`.
- Added inbox watch UI controls and output in the Social Inbox panel.
- Added automation integration so Social inbox attention contributes to automation issue reporting.
- Added notifications for inbox attention, stale backlog, unassigned backlog, and high-priority backlog transitions.

## Why it matters
- Social inbox triage now reaches the notification and automation layers.
- The system can surface stale or unassigned threads before they disappear into the backlog.
- Automation summaries now treat inbox handling gaps as operational issues.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
