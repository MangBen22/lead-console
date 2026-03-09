# Phase 104: Release Gate Transition Direction Filter

## Summary
Added transition-direction filtering so gate status changes can be isolated by direction (`to_blocked` vs `to_allowed`).

## Delivered
- Extended Gate runs filtering with:
  - `transition_to`
    - `all`
    - `to_blocked`
    - `to_allowed`
- Added UI control:
  - `Transition to filter`
- Persisted transition-direction filter in local storage and reset/preset flows.
- Updated API phase marker:
  - `1.90-release-gate-transition-direction-filter`

## Ops Notes
- `transition_to=to_blocked` highlights regressions.
- `transition_to=to_allowed` highlights recovery flips.
