# Phase 84: Release Gate Digest Panel

## Summary
Added a human-readable gate-watch digest panel for faster operator scanning of release gate run trends.

## Delivered
- Updated Go-Live UI:
  - added `releaseGateRunDigestView`
- Added frontend formatter:
  - `formatReleaseGateRunDigest(summary, filters)`
  - renders totals, blocker run counts, and top failed items
- `loadReleaseGateRuns()` now updates:
  - raw runs JSON
  - summary JSON
  - plain-text digest panel
- Updated API phase marker:
  - `1.70-release-gate-digest-panel`

## Ops Notes
- Digest is optimized for rapid triage and handoff notes.
- Raw JSON remains available for full forensic detail.
