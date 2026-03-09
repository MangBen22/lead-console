# Phase 103: Release Gate Recent Ratio Filter

## Summary
Added recent blocked-ratio threshold filtering so operators can focus on runs with stronger sustained-blocking intensity.

## Delivered
- Extended Gate runs filtering with:
  - `recent_ratio_min`
- Added UI control:
  - `Recent ratio min %`
- Persisted recent-ratio filter in local storage and reset/preset flows.
- Updated API phase marker:
  - `1.89-release-gate-recent-ratio-filter`

## Ops Notes
- Higher `recent_ratio_min` values quickly isolate the most severe sustained-blocking windows.
