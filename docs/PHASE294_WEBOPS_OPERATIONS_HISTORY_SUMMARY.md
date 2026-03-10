# Phase 294: WebOps Operations History Summary

## Summary
- Added aggregated WebOps operations history summaries with averages and first-vs-latest deltas.

## API
- `webops.operations.history_summary`
  - Input:
    - `limit`
  - Returns:
    - run count
    - latest and oldest history entries
    - averages for key WebOps operations metrics
    - changes between latest and oldest runs

## UI
- Added a WebOps operations history summary view under the WebOps operations section.
