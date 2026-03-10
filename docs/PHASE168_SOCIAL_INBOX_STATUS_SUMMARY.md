# Phase 168: Social Inbox Status Summary

## What changed
- Expanded inbox summary counts to track `open`, `pending`, `replied`, `closed`, and `backlog`.
- Added provider-level status breakdowns for the same states.
- Added backlog-specific timeline output in the Social inbox summary API.
- Updated Social operations snapshot and Social module metrics to report backlog and pending thread counts.

## Why it matters
- Inbox metrics now match the triage workflow introduced in the previous phase.
- Pending threads are no longer hidden inside the generic open count.
- Operations views can distinguish unread load from the broader handling backlog.

## Validation
- `php -l app-site/api/index.php`
- `php -l lead-console.php`
