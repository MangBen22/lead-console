# Phase 60: Watchdogs Incident Action Notes

## Summary
Added operator note capture for watchdog incident resolve/reopen quick actions in Go-Live.

## Delivered
- Added Go-Live input:
  - `watchdogsIncidentNoteInput`
- Quick actions now submit optional note payloads:
  - `deployment.watchdogs.incident.resolve`
  - `deployment.watchdogs.incident.reopen`
- Updated operator runbook guidance to include note usage.
- Updated API phase marker:
  - `1.46-watchdogs-incident-action-notes`

## Ops Notes
- Always provide concise remediation context in action notes for audit clarity.
