# Phase 167: Social Inbox Workflow Update

## What changed
- Added normalized inbox thread fields for `priority`, `owner`, `internal_note`, and `last_status_at`.
- Added `social.inbox.update` so operators can triage inbox threads without sending a reply.
- Extended the Social Inbox screen with update controls for status, priority, owner, and internal note.
- Added client-side refresh handling so inbox changes immediately update the inbox summary, inbox list, activity feed, and Social module card.

## Why it matters
- Social inbox handling is no longer limited to a single reply action.
- Operators can assign and triage threads before a reply is sent.
- Existing stored inbox rows are normalized automatically so old data remains compatible.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
