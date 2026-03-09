# Phase 120: Release Gate Operations Snapshot Export

## Summary
Completed operations snapshot workflow by adding UI refresh/download controls and export endpoint support.

## Delivered
- Added API action:
  - `deployment.release.gate.operations.snapshot.export`
- Added UI controls:
  - `Refresh Ops Snapshot`
  - `Download Ops Snapshot`
  - `releaseGateOpsSnapshotView`
- Operations snapshot panel now loads alongside runs/meta/quickstats refresh.
- Updated API phase marker:
  - `2.06-release-gate-operations-snapshot-export`

## Ops Notes
- Use Ops Snapshot as the single capture artifact for quickstats + metadata + applied filter context.
