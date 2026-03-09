# Phase 65: Watchdogs Policy History

## Summary
Added persistent policy change history for watchdog threshold settings, with dashboard visibility and actor/source tracking.

## Delivered
- Added storage:
  - `deployment_watchdogs_policy_history`
- Added helper:
  - `deployment_watchdogs_policy_history_append(previous, current, source)`
- Extended policy save flow:
  - records previous/current values
  - stores actor + source + timestamp history entry
  - returns saved history entry in response
- Added API endpoint:
  - `GET /api/index.php?action=deployment.watchdogs.policy.history`
- Updated Go-Live UI:
  - `Refresh Watchdogs Policy History` button
  - policy history panel
- Updated API phase marker:
  - `1.51-watchdogs-policy-history`

## Ops Notes
- Policy history gives auditable context for threshold drift before and during cutover windows.
