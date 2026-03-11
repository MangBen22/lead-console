# Phase 314: Launch Operations History Filters

- Added filtered launch operations history listing.
- Added helpers:
  - `launch_operations_history_apply_filters()`
  - `launch_operations_history_list_snapshot()`
- History now supports:
  - `source`
  - `launch_state`
  - `search`
  - `page`
  - `limit`
- Updated API actions:
  - `launch.operations.history`
  - `launch.operations.history_export`
- Updated the Launch Operations section with history filter controls.
- Updated app wiring so launch history load and export use the current filter state.

This turns launch history into a navigable review workspace instead of a raw unfiltered dump.
