# Phase 102: Release Gate Reason Count Filter

## Summary
Added reason-count range filtering so Gate Runs can be constrained to low-noise or high-noise checks.

## Delivered
- Extended Gate runs filtering with:
  - `reason_count_min`
  - `reason_count_max`
- Added UI controls:
  - `Reason count min`
  - `Reason count max`
- Persisted reason-count filters in local storage and reset/preset flows.
- Updated API phase marker:
  - `1.88-release-gate-reason-count-filter`

## Ops Notes
- Use higher `reason_count_min` to isolate runs with many blockers/reasons.
- Use lower `reason_count_max` to inspect near-clean runs quickly.
