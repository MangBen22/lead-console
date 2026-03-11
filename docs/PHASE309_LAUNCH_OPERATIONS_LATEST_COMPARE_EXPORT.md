# Phase 309: Launch Operations Latest Compare Export

- Added export coverage for launch operations latest compare.
- Added API action:
  - `GET /api/index.php?action=launch.operations.latest_compare_export`
- Export includes:
  - latest compare summary
  - latest snapshot record
  - previous snapshot record
  - metric deltas
- Added a launch compare download action in the Launch Operations section.
- Updated app wiring so compare exports also refresh the compare view payload in the dashboard.

This makes launch drift evidence exportable for review and escalation workflows.
