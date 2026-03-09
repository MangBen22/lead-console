# Phase 80: Release Gate Filter Persistence

## Summary
Added local persistence for gate-run filters so the Go-Live investigation context survives page reloads.

## Delivered
- Added browser storage key:
  - `lc_release_gate_runs_filters_v1`
- Gate filters now auto-save when collected for API calls.
- Gate filter reset now clears local storage.
- Added restore-on-load behavior for:
  - limit
  - status
  - source
  - failed item
- Updated API phase marker:
  - `1.66-release-gate-filter-persistence`

## Ops Notes
- Stored filters are browser-local and per user/session context.
- Use `Clear Gate Filters` before broad readiness reviews to avoid stale scoped views.
