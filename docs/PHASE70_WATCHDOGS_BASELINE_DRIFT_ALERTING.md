# Phase 70: Watchdogs Baseline Drift Alerting

## Summary
Integrated policy baseline drift signals into the watchdog check cycle so drift changes trigger notifications and are recorded in watchdog run/state telemetry.

## Delivered
- Extended `deployment_watchdogs_check_snapshot()` to include baseline state:
  - `baseline_has_baseline`
  - `baseline_has_changes`
  - `baseline_changed_count`
  - baseline delta metadata
- Added baseline drift transition alerting:
  - warns when drift appears
  - success alert when drift clears
  - warning when baseline becomes missing
  - info alert when baseline is configured
- Added audit events:
  - `deployment / watchdogs.policy.baseline.drift`
- Extended watchdog run/state payloads with baseline telemetry fields.
- Updated API phase marker:
  - `1.56-watchdogs-baseline-drift-alerting`

## Ops Notes
- Baseline drift transitions now show in normal watchdog run history.
- This improves signal quality for operators relying on scheduler-driven watchdog checks.
