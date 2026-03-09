# Phase 114: Release Gate Noise Presets

## Summary
Added one-click presets for high-noise and low-noise run segments based on reason-count thresholds.

## Delivered
- Added preset buttons:
  - `Preset: High Noise`
  - `Preset: Low Noise`
- Added preset handler:
  - `applyReleaseGateRunsNoisePreset()`
- Presets reset unrelated filters and apply reason-count thresholds.
- Updated API phase marker:
  - `2.00-release-gate-noise-presets`

## Ops Notes
- `High Noise` starts with `reason_count_min=3`.
- `Low Noise` starts with `reason_count_max=1`.
