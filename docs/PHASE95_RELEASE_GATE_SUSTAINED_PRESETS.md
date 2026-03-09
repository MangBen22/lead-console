# Phase 95: Release Gate Sustained Presets

## Summary
Added one-click presets for sustained trend analysis so operators can switch quickly between active prolonged-block windows and cleared recovery windows.

## Delivered
- Added Gate preset buttons:
  - `Preset: Sustained Active`
  - `Preset: Sustained Clear`
- Added preset handler `applyReleaseGateRunsSustainedPreset()` in app UI logic.
- Presets reset source/failed-item filters and apply consistent limit/window/transition settings.
- Updated API phase marker:
  - `1.81-release-gate-sustained-presets`

## Ops Notes
- `Sustained Active` uses `allowed=blocked` + `sustained=active` for incident-first triage.
- `Sustained Clear` uses `allowed=all` + `sustained=clear` for post-fix stability review.
