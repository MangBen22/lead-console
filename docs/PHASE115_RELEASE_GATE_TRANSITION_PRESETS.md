# Phase 115: Release Gate Transition Presets

## Summary
Added one-click presets for transition-direction filtering.

## Delivered
- Added preset buttons:
  - `Preset: To Blocked`
  - `Preset: To Allowed`
- Added preset handler:
  - `applyReleaseGateRunsTransitionPreset()`
- Presets apply `status_change=changed` with selected `transition_to` value and reset unrelated filters.
- Updated API phase marker:
  - `2.01-release-gate-transition-presets`

## Ops Notes
- Use `To Blocked` to inspect regressions quickly.
- Use `To Allowed` to inspect recovery flips quickly.
