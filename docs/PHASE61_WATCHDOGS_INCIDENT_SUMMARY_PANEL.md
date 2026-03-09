# Phase 61: Watchdogs Incident Summary Panel

## Summary
Added a dedicated watchdog-incident summary snapshot and Go-Live panel for faster watchdog incident health review.

## Delivered
- Added API summary helper:
  - `deployment_watchdogs_incident_summary_snapshot()`
- Added API endpoint:
  - `GET /api/index.php?action=deployment.watchdogs.incident.summary`
- Enhanced existing watchdog incident endpoints:
  - `open` and `latest` now include watchdog summary payload
  - `resolve` and `reopen` responses now include `watchdogs_summary`
- Added Go-Live UI:
  - `Refresh Watchdogs Incident Summary` button
  - watchdog incident summary view panel
- Integrated summary refresh in:
  - initial load
  - scheduler test tick refresh path
  - watchdog incident quick-action refresh flows
- Updated API phase marker:
  - `1.47-watchdogs-incident-summary`

## Ops Notes
- Use summary counts as the first checkpoint before scanning full incident logs.
