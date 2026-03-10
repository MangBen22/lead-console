# Phase 287: Social Operations History

## Summary
- Added persisted Social operations snapshot history.

## API
- `social.operations.snapshot`
  - now records each manual snapshot request into history
- `social.operations.history`
  - Input:
    - `limit`
  - Returns:
    - recent Social operations snapshot history

## UI
- Added a Social operations history view under the Social operations section.
