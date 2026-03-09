# Phase 101: Release Gate Source Group Filter

## Summary
Added a source-group filter so Gate Runs can be segmented quickly into scheduler-driven versus manual/operator-driven checks.

## Delivered
- Extended Gate runs filtering with `source_group`:
  - `all`
  - `scheduler`
  - `manual`
- Added UI control:
  - `Source group filter`
- Persisted source-group filter in local storage and reset/preset flows.
- Updated API phase marker:
  - `1.87-release-gate-source-group-filter`

## Ops Notes
- Use `source_group=scheduler` for unattended cron behavior analysis.
- Use `source_group=manual` to isolate operator-triggered test/debug runs.
