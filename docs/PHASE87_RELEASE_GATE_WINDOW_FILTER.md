# Phase 87: Release Gate Window Filter

## Summary
Added a `window` filter for release-gate run analysis, allowing review of only the most recent N runs before additional filtering.

## Delivered
- Extended shared gate-runs filtering helper with:
  - `window` (0..400; `0` = all available)
- Filtering behavior now applies in order:
  1) recent window slice
  2) allowed/source/failed-item filters
  3) limit
- Updated Go-Live UI:
  - `Gate runs window` input
- Response payloads now include:
  - `window_total_count`
  - report/export equivalents (`runs_window_total_count`)
- Updated API phase marker:
  - `1.73-release-gate-window-filter`

## Ops Notes
- Use smaller windows (e.g., 50) during active incidents to focus on immediate behavior.
- Keep window `0` for full-history investigations.
