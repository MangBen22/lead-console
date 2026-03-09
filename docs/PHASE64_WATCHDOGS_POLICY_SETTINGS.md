# Phase 64: Watchdogs Policy Settings

## Summary
Added configurable watchdog automation policy settings for escalation and auto-resolution thresholds.

## Delivered
- Added deployment guard policy fields:
  - `watchdogs_auto_incident_threshold`
  - `watchdogs_auto_resolve_ok_streak`
- Watchdog check runner now uses saved policy thresholds (bounded `1..10`).
- Added API endpoints:
  - `GET /api/index.php?action=deployment.watchdogs.policy.get`
  - `POST /api/index.php?action=deployment.watchdogs.policy.save`
- Added Go-Live UI controls:
  - `Auto-incident threshold (critical streak)`
  - `Auto-resolve threshold (OK streak)`
  - `Save Watchdogs Policy`
  - policy response panel
- Updated API phase marker:
  - `1.50-watchdogs-policy-settings`

## Ops Notes
- Lower escalation thresholds increase sensitivity and incident volume.
- Higher auto-resolve thresholds reduce false recoveries but delay closure.
