# Phase 44: Persistent Release Gate Settings

## Summary
Added persistent release-gate settings so operators can control gate strictness and freshness policy without code changes.

## Delivered
- Added gate settings to deployment guard storage:
  - `release_gate_freshness_minutes`
  - `release_gate_require_readiness`
  - `release_gate_require_public_smoke`
  - `release_gate_require_auth_smoke`
- Added API endpoints:
  - `GET /api/index.php?action=deployment.release.gate.settings.get`
  - `POST /api/index.php?action=deployment.release.gate.settings.save` (owner + CSRF)
- Updated gate evaluator:
  - `deployment_release_gate_snapshot()` now uses saved settings by default.
  - Optional `freshness_minutes` query/body override still supported.
- Updated UI in `Go-Live Status`:
  - checkboxes for required gate checks
  - persisted freshness window input
  - `Save Release Gate Settings` button
  - settings output panel
- Added observability:
  - settings save emits audit event + info notification
- Updated API phase marker:
  - `1.30-release-gate-settings`

## Ops Notes
- Recommended production baseline:
  - freshness: `30` minutes
  - require readiness: enabled
  - require public smoke: enabled
  - require auth smoke: enabled
