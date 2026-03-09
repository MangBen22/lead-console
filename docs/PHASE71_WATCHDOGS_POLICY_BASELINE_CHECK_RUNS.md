# Phase 71: Watchdogs Policy Baseline Check Runs

## Summary
Added a dedicated baseline-check run stream with scheduler integration, so policy drift checks are independently runnable, logged, and visible in scheduler telemetry.

## Delivered
- Added storage:
  - `deployment_watchdogs_policy_baseline_state.json`
  - `deployment_watchdogs_policy_baseline_runs.json`
- Added helper:
  - `deployment_watchdogs_policy_baseline_check_snapshot(source)`
- Added API endpoints:
  - `POST /api/index.php?action=deployment.watchdogs.policy.baseline.check`
  - `GET /api/index.php?action=deployment.watchdogs.policy.baseline.runs`
- Integrated baseline checks into scheduler tick paths:
  - disabled
  - interval-skip
  - normal run
- Extended scheduler status payload:
  - `watchdogs_policy_baseline_check_state`
  - `watchdogs_policy_baseline_check_last_run`
- Extended cutover evidence bundle with baseline-check state/runs.
- Updated dashboard UI with:
  - `Run Baseline Check`
  - `Refresh Baseline Checks`
  - baseline-check output panel
- Updated API phase marker:
  - `1.57-watchdogs-policy-baseline-check-runs`

## Ops Notes
- Use `Run Baseline Check` before release gate verification to capture a current drift snapshot.
- Scheduler now continuously updates baseline-check telemetry even when automation is skipped.
