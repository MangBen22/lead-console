# Phase 45: Release Gate Watchdog and Scheduler Integration

## Summary
Added a release-gate watchdog that tracks gate state transitions, records a run history, emits alerts on changes, and runs automatically during scheduler ticks.

## Delivered
- Added storage paths:
  - `deployment_release_gate_state.json`
  - `deployment_release_gate_runs.json`
- Added watchdog evaluator:
  - `deployment_release_gate_watch_snapshot($source, $freshnessMinutes)`
  - Records run history and current state.
  - Sends notifications on:
    - transition to `BLOCKED` (critical)
    - transition to `ALLOWED` (success)
    - periodic blocked reminder (warning, cooldown)
- Added API endpoints:
  - `POST /api/index.php?action=deployment.release.gate.watch` (owner + CSRF)
  - `GET /api/index.php?action=deployment.release.gate.runs`
- Scheduler integration:
  - `automation.scheduler.tick` now executes release-gate watchdog in:
    - disabled skip path
    - interval-not-reached skip path
    - normal run path
  - `automation.scheduler.status` now returns `release_gate_watch_state`
- Dashboard updates in `Go-Live Status`:
  - `Run Gate Watch` button
  - `Refresh Gate Runs` button
  - watch result panel + run history panel
- Updated API phase marker:
  - `1.31-release-gate-watchdog`

## Ops Notes
- Use gate watchdog runs as operational evidence for release readiness monitoring.
- Scheduler-triggered watchdog gives ongoing signal even when full automation is skipped.
