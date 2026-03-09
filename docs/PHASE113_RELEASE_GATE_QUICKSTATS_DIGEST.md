# Phase 113: Release Gate Quickstats Digest

## Summary
Added a server-generated compact digest string to quickstats for handoff-friendly status summaries.

## Delivered
- Extended quickstats response with:
  - `quick_digest`
- Digest includes count, blocked ratio, status-changed ratio, sustained state, recent sustained ratio, and transition count.
- Updated API phase marker:
  - `1.99-release-gate-quickstats-digest`

## Ops Notes
- Use `quick_digest` in incident notes or deployment chat updates when full JSON is unnecessary.
