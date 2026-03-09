# Phase 72: Release Gate Baseline Check Freshness

## Summary
Added a release-gate rule that requires a recent successful watchdog policy baseline check run before launch approval.

## Delivered
- Added deployment guard flag:
  - `release_gate_require_watchdogs_policy_baseline_check`
- Extended release gate snapshot:
  - new check `watchdogs_policy_baseline_check_ok_and_fresh`
  - uses gate freshness window to validate baseline-check recency
- Extended release gate settings APIs:
  - get/save now include `require_watchdogs_policy_baseline_check`
- Updated dashboard settings UI:
  - checkbox `Require recent watchdogs baseline check`
- Updated Hostinger runbook with baseline-check freshness guidance.
- Updated API phase marker:
  - `1.58-release-gate-baseline-check-freshness`

## Ops Notes
- Pair this with `Require watchdogs policy baseline match` for strict launch controls.
- Freshness is measured using the same release gate window (minutes) configured in Go-Live settings.
