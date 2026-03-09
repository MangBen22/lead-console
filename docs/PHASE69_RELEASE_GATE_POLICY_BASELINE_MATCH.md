# Phase 69: Release Gate Policy Baseline Match

## Summary
Added a release-gate requirement that blocks launch approval when watchdog policy settings drift from the saved baseline.

## Delivered
- Added deployment guard flag:
  - `release_gate_require_watchdogs_policy_baseline_match`
- Extended release gate snapshot:
  - new check `watchdogs_policy_baseline_match`
  - supports strict requirement toggle
  - includes baseline snapshot context in gate payload
- Extended release gate settings APIs:
  - `deployment.release.gate.settings.get`
  - `deployment.release.gate.settings.save`
  - new field `require_watchdogs_policy_baseline_match`
- Updated dashboard settings UI:
  - checkbox `Require watchdogs policy baseline match`
- Updated Hostinger runbook with baseline-match gate guidance.
- Updated API phase marker:
  - `1.55-release-gate-policy-baseline-match`

## Ops Notes
- Keep this disabled while tuning thresholds during initial setup.
- Enable it for production cutover windows to prevent accidental policy drift.
