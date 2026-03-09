# Phase 106: Release Gate Failed Item Match Mode

## Summary
Added failed-item match mode controls so filtering can be strict (`exact`) or fuzzy (`contains`).

## Delivered
- Extended Gate runs filtering with:
  - `failed_item_mode`
    - `exact`
    - `contains`
- Added UI control:
  - `Failed item mode`
- Persisted failed-item mode in local storage and reset/preset flows.
- Updated API phase marker:
  - `1.92-release-gate-failed-item-mode`

## Ops Notes
- Use `contains` when item names vary by suffix/prefix.
- Use `exact` for precise blocker tracking and audits.
