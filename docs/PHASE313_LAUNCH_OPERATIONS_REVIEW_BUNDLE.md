# Phase 313: Launch Operations Review Bundle

- Added a single launch review bundle export.
- Added API action:
  - `GET /api/index.php?action=launch.operations.review_bundle`
- Bundle includes:
  - current launch snapshot
  - launch history slice
  - launch history summary
  - latest compare
  - issues summary
- Added a launch review bundle download action in the Launch Operations section.
- Updated app wiring so review bundle exports can be generated directly from the dashboard.

This produces one operator-ready artifact for launch review instead of requiring multiple separate exports.
