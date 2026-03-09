# Phase 98: Release Gate Status Change Filter

## Summary
Added a status-change filter to Gate Runs so operators can isolate runs where release-gate status flipped versus runs that remained stable.

## Delivered
- Extended Gate runs filtering with `status_change`:
  - `all`
  - `changed`
  - `stable`
- Added UI control:
  - `Status change filter`
- Persisted status-change filter in browser storage and reset/preset flows.
- Updated API phase marker:
  - `1.84-release-gate-status-change-filter`

## Ops Notes
- `status_change=changed` highlights runs that changed gate state and may need immediate review.
- `status_change=stable` helps analyze long unchanged periods for drift/noise patterns.
