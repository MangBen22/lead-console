# Phase 293: WebOps Operations History

## Summary
- Added persisted WebOps operations snapshot history.

## API
- `webops.operations.snapshot`
  - now records each manual snapshot request into history
- `webops.operations.history`
  - Input:
    - `limit`
  - Returns:
    - recent WebOps operations snapshot history

## UI
- Added a WebOps operations history view under the WebOps operations section.
