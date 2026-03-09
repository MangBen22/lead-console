# Phase 47: Cutover Signoff Workflow

## Summary
Added a persistent cutover signoff workflow so release readiness can be formally recorded from the dashboard after gate checks pass.

## Delivered
- Added signoff storage:
  - `deployment_cutover_signoffs.json`
- Added API endpoints:
  - `POST /api/index.php?action=deployment.cutover.signoff.create` (owner + CSRF)
  - `GET /api/index.php?action=deployment.cutover.signoff.list`
  - `GET /api/index.php?action=deployment.cutover.signoff.latest`
- Added signoff logic:
  - Signoff creation automatically snapshots a fresh cutover evidence bundle.
  - Signoff is blocked when release gate is not allowed.
  - Signoff record stores:
    - signoff ID, actor, timestamp, note
    - linked evidence bundle ID
    - SHA256 hash of evidence payload
    - key summary metrics (readiness, incidents, SLA, pipeline status)
- Added dashboard controls in `Hosting Cutover Toolkit -> Cutover Readiness`:
  - `Create Cutover Signoff`
  - `Refresh Signoffs`
  - `Download Latest Signoff`
  - signoff result and signoff history views
- Added observability:
  - audit events for signoff create/block
  - notifications for signoff success/block
- Updated API phase marker:
  - `1.33-cutover-signoff-workflow`

## Ops Notes
- Signoff creation is intentionally gated by current release gate policy.
- Archive both artifacts for launch records:
  - cutover evidence bundle
  - latest signoff JSON
