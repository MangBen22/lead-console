# Phase 317: Launch Operations Source Summary

- Added source-level rollups for filtered launch operations history.
- Added helper:
  - `launch_operations_source_summary_snapshot()`
- Added API action:
  - `GET /api/index.php?action=launch.operations.source_summary`
- Source summary reports:
  - filtered count
  - source count
  - launch-state totals across filtered history
  - per-source run counts and latest launch state
- Added a source summary view to the Launch Operations section.
- Updated launch refresh behavior to reload source summary together with launch history.

This makes it easier to see whether launch issues are coming from manual checks, manual automation, or scheduler runs.
