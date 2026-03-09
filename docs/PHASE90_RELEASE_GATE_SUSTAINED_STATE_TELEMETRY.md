# Phase 90: Release Gate Sustained State Telemetry

## Summary
Exposed sustained-blocked release-gate state as a first-class payload in scheduler and gate APIs so operators can detect prolonged blockage quickly without parsing raw state blobs.

## Delivered
- Added normalized `sustained_state` to `deployment.release.gate.runs` response.
- Added `release_gate_sustained_state` to `deployment.release.gate.blockers.report` exports.
- Added `release_gate_sustained_state` to `automation.scheduler.status` telemetry output.
- Updated scheduler gate summary panel payload to include sustained-state telemetry in the app UI.
- Updated API phase marker:
  - `1.76-release-gate-sustained-state-telemetry`

## Ops Notes
- `release_gate_sustained_state.active=1` means sustained blocked trend is currently active.
- Use `recent_ratio_percent` + `recent_window_runs` to assess severity and confidence before escalation.
- `last_changed_at` and `last_alert_at` help correlate trend transitions with watch notifications.
