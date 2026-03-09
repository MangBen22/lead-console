# Phase 89: Release Gate Sustained Blocked Alert

## Summary
Added sustained-blocked trend detection for release-gate watch runs to highlight prolonged blocked states.

## Delivered
- `deployment_release_gate_watch_snapshot()` now computes recent blocked trend over the latest 20 runs.
- Added sustained trend fields to run/state telemetry:
  - `recent_blocked_ratio_percent`
  - `recent_window_runs`
  - `sustained_blocked_active`
  - `sustained_blocked_alert_sent`
  - state-level sustained-blocked timestamps and ratio fields
- Added notifications/audit for sustained trend transitions:
  - alert on activation
  - alert on clearance
  - periodic reminder when still active (cooldown protected)
  - audit event `deployment / release.gate.watch.sustained_blocked`
- Updated API phase marker:
  - `1.75-release-gate-sustained-blocked-alert`

## Ops Notes
- Sustained trend activates at >=10 recent runs with blocked ratio >=70%.
- Use together with source and failed-item filters for focused remediation planning.
