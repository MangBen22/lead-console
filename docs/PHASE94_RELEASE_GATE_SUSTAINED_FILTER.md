# Phase 94: Release Gate Sustained Filter

## Summary
Added a sustained-state filter to gate run queries so operators can focus on periods where sustained blocking was active or already clear.

## Delivered
- Extended `deployment_release_gate_runs_apply_filters()` with `sustained` filter values:
  - `all`
  - `active`
  - `clear`
- Included `sustained` in `applied_filters` payloads.
- Added Gate Runs UI control:
  - `Sustained filter` select (`all/active/clear`)
- Persisted sustained filter in local storage and reset/preset workflows.
- Updated API phase marker:
  - `1.80-release-gate-sustained-filter`

## Ops Notes
- `sustained=active` quickly isolates prolonged blocked trend windows.
- `sustained=clear` helps validate recovery stability after remediation.
