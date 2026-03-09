# Phase 67: Watchdogs Policy Restore Preview

## Summary
Added a restore preview endpoint and dashboard action so operators can inspect watchdog policy deltas before applying a rollback.

## Delivered
- Added shared helpers:
  - `deployment_watchdogs_policy_history_rows()`
  - `deployment_watchdogs_policy_history_select(rows, history_id)`
  - `deployment_watchdogs_policy_diff_summary(current, candidate)`
- Added API endpoint:
  - `GET /api/index.php?action=deployment.watchdogs.policy.preview`
  - supports optional `history_id`
  - supports `mode=previous|current`
  - returns current policy, candidate policy, field-level delta, and changed count
- Updated restore flow:
  - reuses shared history-selection helpers
  - blocks no-op restore writes when selected snapshot already matches current settings
  - includes delta details in restore response and audit payload
- Updated Go-Live UI:
  - added `Preview Restore Diff` action button
  - added restore preview output panel
- Updated API phase marker:
  - `1.53-watchdogs-policy-restore-preview`

## Ops Notes
- Run `Preview Restore Diff` first when using manual `history_id` values.
- No-op restore requests now return success with `no_change=1` and do not write new history rows.
