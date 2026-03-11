# Phase 306: Launch Operations Latest Compare

- Added latest-vs-previous compare support for launch operations history.
- Added helper:
  - `launch_operations_latest_compare()`
- Compare output now reports:
  - latest and previous timestamps
  - latest and previous launch state
  - deltas for gate, readiness, watchdog, incident, automation, and module issue totals
- Added API action:
  - `GET /api/index.php?action=launch.operations.latest_compare`
- Added a latest compare view to the Launch Operations section in the dashboard.
- Updated launch refresh behavior to reload the compare output together with snapshot, history, and issues.

This gives operators a direct before-vs-now readout for launch readiness drift.
