# Phase 315: Launch Operations History Detail

- Added detail lookup for individual launch operations history snapshots.
- Added helper:
  - `launch_operations_history_detail_snapshot()`
- Added API action:
  - `GET /api/index.php?action=launch.operations.history_detail&snapshot_id=...`
- Updated the Launch Operations section with a history detail form and result view.
- Added app wiring to load a specific launch history snapshot by ID.

This makes it possible to inspect one launch review record without scanning the entire history list.
