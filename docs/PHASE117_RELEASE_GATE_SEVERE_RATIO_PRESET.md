# Phase 117: Release Gate Severe Ratio Preset

## Summary
Added a severe-ratio preset for rapid focus on high-intensity sustained-block windows.

## Delivered
- Added preset button:
  - `Preset: Severe Ratio`
- Added preset handler:
  - `applyReleaseGateRunsSevereRatioPreset()`
- Preset applies:
  - `recent_ratio_min=70`
  - `allowed=blocked`
  - `sustained=active`
- Updated API phase marker:
  - `2.03-release-gate-severe-ratio-preset`

## Ops Notes
- Use this preset first during escalation to isolate highest-risk sustained-block segments.
