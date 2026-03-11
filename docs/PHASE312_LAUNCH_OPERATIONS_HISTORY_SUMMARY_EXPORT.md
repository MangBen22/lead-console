# Phase 312: Launch Operations History Summary Export

- Added export coverage for launch operations history summary.
- Added API action:
  - `GET /api/index.php?action=launch.operations.history_summary_export`
- Export includes:
  - launch history summary metrics
  - latest and oldest launch history records
  - backing history slice used for the summary
- Added a launch history summary download action in the Launch Operations section.
- Updated app wiring so history summary exports also refresh the summary view payload in the dashboard.

This makes launch trend summaries easy to attach to review and handoff workflows.
