# Phase 50: Cutover Signoff Lifecycle Control

## Summary
Added lifecycle controls for cutover signoffs so teams can explicitly activate the signoff used by release gate checks and revoke invalid signoffs with an audit trail.

## Delivered
- Signoff lifecycle fields added:
  - `active` flag
  - `status` (`approved` / `revoked`)
  - `revoked_at`, `revoked_by`, `revocation_reason`
- New signoff behavior:
  - Newly created signoff becomes active
  - previous signoffs are auto-deactivated
- Added API endpoints:
  - `GET /api/index.php?action=deployment.cutover.signoff.active`
  - `POST /api/index.php?action=deployment.cutover.signoff.activate`
  - `POST /api/index.php?action=deployment.cutover.signoff.revoke`
- Release gate update:
  - when `require_cutover_signoff` is enabled, gate now checks **active signoff freshness**.
- Dashboard updates in `Cutover Readiness`:
  - Signoff ID + reason inputs
  - `Refresh Active Signoff`
  - `Activate Signoff`
  - `Revoke Signoff`
  - active signoff state panel
- Evidence bundle update:
  - includes active signoff snapshot in cutover signoff section
- Updated API phase marker:
  - `1.36-cutover-signoff-lifecycle-control`

## Ops Notes
- Keep one clear active signoff before launch.
- Revoke signoffs when post-check conditions change (for example incident reopen, failed smoke rerun).
