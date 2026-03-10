# Phase 291: WebOps Operations Snapshot

## Summary
- Added a consolidated WebOps operations snapshot.

## API
- `webops.operations.snapshot`
  - Input:
    - `limit`
  - Returns:
    - monitor, incident, retry, action queue, and action log slices
    - latest WebOps run
    - summary counts for the current WebOps state

## UI
- Added a WebOps Operations Snapshot section with refresh and limit controls.
