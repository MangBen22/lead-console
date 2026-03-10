# Phase 169: Social Inbox Workload Summary

## What changed
- Added `social.inbox.workload` to summarize backlog ownership and priority.
- Added workload counts for high-priority and unassigned inbox backlog.
- Added owner ranking and unassigned backlog thread output.
- Surfaced inbox workload in the Social Inbox UI and Social operations snapshot.

## Why it matters
- Inbox triage fields now drive operational reporting.
- Operators can see whether backlog is assigned and whether urgent items are stacking up.
- Social operations snapshot now exposes inbox staffing pressure, not just raw thread counts.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
