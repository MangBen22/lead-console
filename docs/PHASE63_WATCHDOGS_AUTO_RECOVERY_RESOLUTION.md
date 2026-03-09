# Phase 63: Watchdogs Auto-Recovery Resolution

## Summary
Added auto-resolution behavior so sustained healthy watchdog checks can close open watchdog-origin incidents automatically.

## Delivered
- Extended watchdog check state/run tracking:
  - `ok_streak`
  - `auto_resolve_threshold`
  - auto-resolution run/state fields
- Auto-resolution policy:
  - when watchdog status is `ok` for threshold streak (`2`)
  - and an open watchdog incident exists
  - system auto-resolves that incident
- Added audit and notification events for auto-resolution:
  - `deployment / incident.report.auto_resolve.watchdogs`
  - success notification with run/report context
- Updated API phase marker:
  - `1.49-watchdogs-auto-recovery-resolution`

## Ops Notes
- Auto-resolution complements manual quick actions; operators should still validate root cause is fixed.
