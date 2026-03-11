# Phase 316: Launch Operations History Detail Export

- Added export coverage for a single launch operations history snapshot.
- Added API action:
  - `GET /api/index.php?action=launch.operations.history_detail_export&snapshot_id=...`
- Export includes the selected launch history record.
- Added a launch history detail download action in the Launch Operations section.
- Updated app wiring so the exported detail payload is also shown in the history detail view.

This makes one reviewed launch snapshot easy to archive or share without exporting unrelated history rows.
