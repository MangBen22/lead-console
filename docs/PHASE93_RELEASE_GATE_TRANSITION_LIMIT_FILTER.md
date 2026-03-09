# Phase 93: Release Gate Transition Limit Filter

## Summary
Added operator control for sustained-state transition history depth so gate analytics can be tuned for quick triage or deeper incident review.

## Delivered
- Added `transition_limit` query support (range `1..50`) via:
  - `deployment_release_gate_sustained_transition_limit_from_query()`
- Applied `transition_limit` in:
  - `deployment.release.gate.runs`
  - `deployment.release.gate.runs.export`
  - `deployment.release.gate.blockers.report`
- Added response metadata:
  - `sustained_transition_limit`
  - `release_gate_sustained_transition_limit`
- Added Gate Runs UI input `Sustained transitions` and persisted it in filter storage/presets.
- Updated API phase marker:
  - `1.79-release-gate-transition-limit`

## Ops Notes
- Lower `transition_limit` for concise handoff snapshots.
- Increase `transition_limit` when reconstructing longer sustained-blocking history.
