# Phase 73: Policy Change Baseline Auto-Check

## Summary
Policy-changing actions now automatically trigger watchdog policy baseline checks so baseline freshness and drift telemetry stay current without manual follow-up.

## Delivered
- Auto-run baseline checks after:
  - `deployment.watchdogs.policy.save`
  - `deployment.watchdogs.policy.restore` (including no-change restore)
  - `deployment.watchdogs.policy.baseline.set`
  - `deployment.watchdogs.policy.baseline.clear`
- Added `baseline_check` payloads to affected API responses.
- Updated dashboard refresh behavior to pull baseline-check runs after policy/baseline mutations.
- Updated API phase marker:
  - `1.59-policy-change-baseline-auto-check`

## Ops Notes
- Operators no longer need a separate baseline-check click after policy mutations.
- Release-gate freshness requirements for baseline checks stay easier to satisfy because checks are recorded on each policy update path.
