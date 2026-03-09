# Phase 58: Watchdogs Incident Quick Actions

## Summary
Added Go-Live quick actions to inspect and resolve open watchdog-origin incidents without leaving the deployment monitoring workflow.

## Delivered
- Added API endpoints:
  - `GET /api/index.php?action=deployment.watchdogs.incident.open`
  - `POST /api/index.php?action=deployment.watchdogs.incident.resolve`
- Resolve action behavior:
  - resolves latest open watchdog-origin incident (`incident_origin=watchdogs_check`)
  - writes audit event: `deployment / watchdogs.incident.resolve`
  - sends success notification after resolution
- Updated Go-Live dashboard:
  - `Refresh Watchdogs Incident` button
  - `Resolve Watchdogs Incident` button
  - watchdog incident view panel
- Integrated incident view refresh into:
  - initial page load
  - scheduler test tick refresh
  - watchdog check/status/runs refresh flows
- Updated API phase marker:
  - `1.44-watchdogs-incident-quick-actions`

## Ops Notes
- Use quick resolve only after underlying watchdog failures are fixed.
- Always rerun watchdog checks after resolution to verify stable `ok`/`warning` state.
