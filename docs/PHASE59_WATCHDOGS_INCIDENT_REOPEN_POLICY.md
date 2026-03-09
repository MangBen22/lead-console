# Phase 59: Watchdogs Incident Reopen Policy

## Summary
Improved watchdog incident lifecycle handling by reopening the latest resolved watchdog incident before creating a new one, plus added manual reopen quick action.

## Delivered
- Added helper:
  - `deployment_find_latest_watchdogs_incident(statuses)`
- Auto-escalation update in watchdog checks:
  - if no open watchdog incident exists and threshold is met:
    - reopen latest resolved watchdog incident first
    - create a new incident only when no resolved watchdog incident exists
- Added API endpoints:
  - `GET /api/index.php?action=deployment.watchdogs.incident.latest`
  - `POST /api/index.php?action=deployment.watchdogs.incident.reopen`
- Added Go-Live quick action:
  - `Reopen Watchdogs Incident`
- Added run/state fields:
  - `auto_incident_reopened`
  - `last_auto_incident_reopened_at`
- Updated API phase marker:
  - `1.45-watchdogs-incident-reopen-policy`

## Ops Notes
- Incident history stays cleaner by reusing resolved watchdog incidents when issues reoccur.
- Manual reopen is available when remediation was partial and failures return.
