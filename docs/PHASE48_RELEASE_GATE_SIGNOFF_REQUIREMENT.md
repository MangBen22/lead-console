# Phase 48: Release Gate Signoff Requirement

## Summary
Extended release-gate policy with an optional requirement for a fresh cutover signoff.

## Delivered
- Added release-gate setting:
  - `release_gate_require_cutover_signoff`
- Gate evaluator now supports check:
  - `cutover_signoff_fresh`
  - Passes only when latest signoff exists and is fresh within gate freshness window.
- Added gate payload details:
  - `settings.require_cutover_signoff`
  - `latest_cutover_signoff` (age/freshness details)
- Updated release-gate settings endpoints:
  - `deployment.release.gate.settings.get`
  - `deployment.release.gate.settings.save`
- Updated dashboard `Go-Live Status` UI:
  - `Require recent cutover signoff` checkbox
  - persisted through settings save/load
- Updated API phase marker:
  - `1.34-release-gate-signoff-requirement`

## Ops Notes
- Keep this requirement enabled for production signoff discipline.
- If enabled, you must create a fresh cutover signoff before release gate can pass.
