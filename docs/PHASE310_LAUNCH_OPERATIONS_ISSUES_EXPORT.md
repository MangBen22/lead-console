# Phase 310: Launch Operations Issues Export

- Added export coverage for the launch operations issues summary.
- Added API action:
  - `GET /api/index.php?action=launch.operations.issues_export`
- Export includes:
  - launch issue summary
  - issue list
  - backing launch snapshot used to generate the issues
- Added a launch issues download action in the Launch Operations section.
- Updated app wiring so issue exports also refresh the issues view payload in the dashboard.

This gives operators a direct export of current launch blockers and warnings for review and escalation.
