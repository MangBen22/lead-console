# Phase 53: Release Gate Signoff Watch Policy

## Summary
Added an optional release-gate policy that requires a recent, valid signoff-integrity watchdog run targeting the current active signoff.

## Delivered
- Added deployment guard setting:
  - `release_gate_require_signoff_integrity_watch` (default disabled)
- Extended release gate checks with:
  - `cutover_signoff_integrity_watch_valid_and_fresh`
  - requires watchdog status `valid`
  - requires watchdog run freshness within release-gate window
  - requires watchdog `active_signoff_id` to match current active signoff
- Added release gate output:
  - `active_cutover_signoff_integrity_watch`
- Updated release-gate settings APIs:
  - get/save now include `require_signoff_integrity_watch`
- Updated Go-Live settings UI:
  - `Require recent valid signoff integrity watch` checkbox
- Updated API phase marker:
  - `1.39-release-gate-signoff-watch-policy`

## Ops Notes
- Keep this policy disabled for legacy signoff migration periods.
- Enable it for production cutovers where scheduler watchdog is active and stable.
