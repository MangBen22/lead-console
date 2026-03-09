# Phase 97: Release Gate Sustained Alert Filter

## Summary
Added sustained-alert filtering so operators can focus specifically on gate runs where sustained-blocked notifications were sent.

## Delivered
- Extended Gate runs filtering with `sustained_alert`:
  - `all`
  - `sent`
  - `not_sent`
- Added UI control:
  - `Sustained alert filter`
- Persisted sustained-alert filter in browser storage and reset/preset flows.
- Updated API phase marker:
  - `1.83-release-gate-sustained-alert-filter`

## Ops Notes
- `sustained_alert=sent` is useful for alert-noise review and escalation timelines.
- `sustained_alert=not_sent` highlights blocked trends that did not trigger new sustained notifications.
