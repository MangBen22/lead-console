# Phase 79: Release Gate Runs Export

## Summary
Added filtered gate-runs export so teams can download focused blocker evidence directly from Go-Live.

## Delivered
- Added helper:
  - `deployment_release_gate_runs_apply_filters(runs, query)`
- Refactored `deployment.release.gate.runs` to reuse shared filtering helper.
- Added endpoint:
  - `GET /api/index.php?action=deployment.release.gate.runs.export`
  - returns filtered items + summary + applied filters + export filename
- Updated Go-Live controls:
  - `Download Gate Runs` button (respects active filters)
- Updated API phase marker:
  - `1.65-release-gate-runs-export`

## Ops Notes
- Apply filters first, then export, to keep evidence payloads concise and review-ready.
- Export format matches API shape and includes summary and filters for traceability.
