# Phase 110: Release Gate Latest Run Pointers

## Summary
Added summary pointers for the latest allowed run and latest status-change run.

## Delivered
- Extended runs summary with:
  - `latest_allowed_run`
  - `latest_status_change_run`
- Updated Gate Runs digest rendering with quick pointer lines.
- Updated API phase marker:
  - `1.96-release-gate-latest-run-pointers`

## Ops Notes
- Use `latest_status_change_run` to quickly identify the most recent gate flip event.
