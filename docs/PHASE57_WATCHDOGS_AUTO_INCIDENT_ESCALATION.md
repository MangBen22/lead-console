# Phase 57: Watchdogs Auto-Incident Escalation

## Summary
Added automatic incident escalation when watchdog checks stay critical across consecutive runs.

## Delivered
- Extended watchdog check runner:
  - tracks `critical_streak`
  - adds auto-incident fields on each run/state
- Auto escalation policy:
  - threshold: `2` consecutive critical watchdog checks
  - if no open watchdog-origin incident exists, system auto-creates one
  - if open watchdog incident already exists, run links to existing incident (no duplicate report)
- Auto-created incidents include:
  - `incident_origin = watchdogs_check`
  - watchdog run/source metadata
  - watchdog summary in incident fields
- Added audit and notification events:
  - `deployment / incident.report.auto.watchdogs`
  - critical notification on auto-created incident
- Updated API phase marker:
  - `1.43-watchdogs-auto-incident-escalation`

## Ops Notes
- Repeated critical watchdog checks now become formal incidents automatically.
- Resolve/reopen lifecycle remains controlled from existing incident actions.
