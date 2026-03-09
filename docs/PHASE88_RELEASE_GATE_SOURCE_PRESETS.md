# Phase 88: Release Gate Source Presets

## Summary
Added source-focused gate filter presets to separate scheduler-driven blockers from manual dashboard checks.

## Delivered
- Added Go-Live preset buttons:
  - `Preset: Scheduler Blocked`
  - `Preset: Manual Blocked`
- Added frontend helper:
  - `applyReleaseGateRunsSourcePreset(source)`
- Preset behavior:
  - sets `status=blocked`
  - sets source filter (`scheduler_tick_run` or `dashboard_manual`)
  - clears failed-item filter
  - resets limit/window defaults
  - refreshes gate runs immediately
- Updated API phase marker:
  - `1.74-release-gate-source-presets`

## Ops Notes
- Scheduler preset is useful when validating unattended reliability.
- Manual preset is useful for isolating operator workflows and testing effects.
