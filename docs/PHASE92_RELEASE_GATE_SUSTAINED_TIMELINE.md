# Phase 92: Release Gate Sustained Timeline

## Summary
Added sustained-state transition timeline analytics so operators can track when prolonged release blocking begins/clears and correlate those transitions with run history.

## Delivered
- Added helper normalization functions:
  - `deployment_release_gate_sustained_state_snapshot()`
  - `deployment_release_gate_sustained_timeline_snapshot()`
- Added sustained timeline payloads to:
  - `deployment.release.gate.runs` (`sustained_timeline`)
  - `deployment.release.gate.runs.export` (`sustained_timeline`)
  - `deployment.release.gate.blockers.report` (`release_gate_sustained_timeline`)
  - `automation.scheduler.status` (`release_gate_sustained_timeline`)
- Updated scheduler gate summary and gate-runs digest rendering to include transition timeline digests.
- Updated API phase marker:
  - `1.78-release-gate-sustained-transition-timeline`

## Ops Notes
- `current_streak_runs` + `current_since` show how long the current sustained state has persisted.
- `transition_count` and `latest_transition` accelerate incident timeline reconstruction.
