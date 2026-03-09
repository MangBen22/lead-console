# Phase 68: Watchdogs Policy Baseline Drift

## Summary
Added watchdog policy baseline management so operators can pin expected thresholds and monitor drift against the live policy.

## Delivered
- Added baseline storage:
  - `deployment_watchdogs_policy_baseline.json`
- Added helpers:
  - `deployment_watchdogs_policy_baseline_snapshot()`
  - shared diff output reused for baseline drift
- Added API endpoints:
  - `GET /api/index.php?action=deployment.watchdogs.policy.baseline.get`
  - `POST /api/index.php?action=deployment.watchdogs.policy.baseline.set`
  - `POST /api/index.php?action=deployment.watchdogs.policy.baseline.clear`
- Baseline set supports:
  - current policy snapshot (default)
  - optional history-driven baseline via `history_id` + `mode`
- Added dashboard controls:
  - `Save Policy Baseline`
  - `Refresh Baseline Drift`
  - `Clear Policy Baseline`
  - baseline drift output panel
- Extended cutover evidence bundle with:
  - `watchdogs_policy_baseline`
- Updated API phase marker:
  - `1.54-watchdogs-policy-baseline-drift`

## Ops Notes
- Use history-based baseline saves when preserving a known-good threshold profile.
- Keep `has_changes=0` before launch windows unless intentional temporary overrides are active.
