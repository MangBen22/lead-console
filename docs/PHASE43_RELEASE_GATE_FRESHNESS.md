# Phase 43: Release Gate with Freshness Enforcement

## Summary
Added a release gate that blocks go-live readiness and release-candidate readiness unless cutover readiness is `ready` and passing smoke checks are fresh within a configurable time window.

## Delivered
- Added API endpoint:
  - `GET /api/index.php?action=deployment.release.gate&freshness_minutes=30`
  - Returns gate checks, pass/fail state, reasons, and latest passing smoke ages.
- Added release gate evaluator:
  - `deployment_release_gate_snapshot($freshnessMinutes = 30)`
  - Checks:
    - cutover readiness status is `ready`
    - latest passing `public` smoke is within freshness window
    - latest passing `auth` smoke is within freshness window
- Enforced gate in:
  - `deployment.go_live_status` (status becomes `blocked` when gate fails)
  - `deployment.release.candidate` (candidate stays `blocked` when gate fails)
- Updated release candidate snapshot to include:
  - `release_gate_allowed`
  - `release_gate_reasons`
  - `release_gate_window_minutes`
- Updated dashboard UI:
  - `Go-Live Status` now has freshness window input
  - added `Refresh Release Gate` button
  - added `releaseGateView` output panel
  - release-candidate generation now sends `freshness_minutes`
- Updated API phase marker:
  - `1.29-release-gate-freshness`

## Operational Notes
- Recommended freshness window remains `30` minutes for production cutovers.
- Run `Run Full Smoke Suite` first, then verify `Release Gate` before generating release candidate.
