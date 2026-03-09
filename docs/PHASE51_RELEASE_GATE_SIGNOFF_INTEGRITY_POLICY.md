# Phase 51: Release Gate Active Signoff Integrity Policy

## Summary
Extended release gate policy with an optional integrity requirement for the active cutover signoff.

## Delivered
- Added release-gate setting:
  - `release_gate_require_signoff_integrity` (default enabled)
- Release gate now includes integrity check:
  - `cutover_signoff_integrity_valid`
  - validates active signoff embedded evidence hash + bundle ID match
- Added gate outputs:
  - `active_cutover_signoff`
  - `active_cutover_signoff_integrity`
- Updated release-gate settings APIs:
  - get/save include `require_signoff_integrity`
- Updated Go-Live settings UI:
  - `Require active signoff integrity valid` checkbox
- Updated API phase marker:
  - `1.37-release-gate-signoff-integrity-policy`

## Ops Notes
- Keep integrity requirement enabled in production.
- If integrity fails, regenerate and re-approve a fresh cutover signoff.
