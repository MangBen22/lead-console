# Phase 62: Scheduler Watchdogs Incident Telemetry

## Summary
Extended scheduler telemetry to always include watchdog-incident summary context in status and tick responses.

## Delivered
- `automation.scheduler.status` now includes:
  - `watchdogs_incident_summary`
- `automation.scheduler.tick` responses now include:
  - `watchdogs_incident_summary` for:
    - disabled skip
    - interval skip
    - normal scheduler run
- Updated API phase marker:
  - `1.48-scheduler-watchdogs-incident-telemetry`

## Ops Notes
- Scheduler output now provides direct incident posture without needing separate incident summary calls.
