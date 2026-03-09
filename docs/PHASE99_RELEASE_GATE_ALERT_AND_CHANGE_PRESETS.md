# Phase 99: Release Gate Alert and Change Presets

## Summary
Added presets for sustained-alert and status-change analysis to reduce manual filter setup during incident triage.

## Delivered
- Added preset buttons:
  - `Preset: Sustained Alerted`
  - `Preset: Status Changed`
- Added preset handlers:
  - `applyReleaseGateRunsSustainedAlertPreset()`
  - `applyReleaseGateRunsStatusChangedPreset()`
- Presets align filter combinations for alert-driven and transition-driven investigations.
- Updated API phase marker:
  - `1.85-release-gate-alert-change-presets`

## Ops Notes
- `Sustained Alerted` targets blocked sustained windows where notification noise is highest.
- `Status Changed` isolates release-gate flip events for transition timeline review.
