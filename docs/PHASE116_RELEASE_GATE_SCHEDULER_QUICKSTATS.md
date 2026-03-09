# Phase 116: Release Gate Scheduler Quickstats

## Summary
Exposed release-gate quickstats inside scheduler status to improve unattended monitoring.

## Delivered
- Added helper:
  - `deployment_release_gate_quick_digest_snapshot()`
- Scheduler status now includes:
  - `release_gate_quickstats`
- Updated scheduler gate summary panel rendering to show quickstats payload.
- Updated API phase marker:
  - `2.02-release-gate-scheduler-quickstats`

## Ops Notes
- Use scheduler quickstats + quick digest to detect trend shifts without opening full run history.
