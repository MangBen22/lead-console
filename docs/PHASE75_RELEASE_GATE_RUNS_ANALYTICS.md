# Phase 75: Release Gate Runs Analytics

## Summary
Added release-gate run analytics so operators can quickly identify recurring blockers and remediation priority from gate watch history.

## Delivered
- Added helper:
  - `deployment_release_gate_runs_summary(runs)`
- Extended endpoint:
  - `GET /api/index.php?action=deployment.release.gate.runs`
  - now returns `summary` with:
    - run totals (`allowed/blocked`)
    - alert/status-change counts
    - blocker-specific blocked-run counters
    - failed-item frequency map + top failed items
    - latest blocked run snapshot
- Extended cutover evidence bundle:
  - `release_gate_watch.summary`
- Updated API phase marker:
  - `1.61-release-gate-runs-analytics`

## Ops Notes
- `summary.top_failed_items` provides a quick triage list before launch.
- Blocker counters help separate baseline policy failures from signoff/watch freshness failures.
