# Phase 66: Watchdogs Policy Restore

## Summary
Added policy restore workflow so watchdog threshold settings can be rolled back from history entries.

## Delivered
- Added reusable policy helper:
  - `deployment_watchdogs_policy_settings_from_guard()`
- Added API endpoint:
  - `POST /api/index.php?action=deployment.watchdogs.policy.restore`
  - supports:
    - optional `history_id` (defaults to latest history entry)
    - `mode` = `previous` or `current`
- Restore flow writes:
  - guard settings update
  - new history entry (restore action)
  - audit event `deployment / watchdogs.policy.restore`
  - info notification
- Added Go-Live controls:
  - `Policy history ID (optional for restore)`
  - `Restore mode` selector (`previous`/`current`)
  - `Restore Watchdogs Policy` button
- Updated API phase marker:
  - `1.52-watchdogs-policy-restore`

## Ops Notes
- `previous` mode is best for rollback.
- `current` mode can reapply a known-good historical configuration.
