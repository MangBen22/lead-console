# Phase 42: One-Click Smoke Suite and Smoke History Export

## Summary
Added a one-click smoke suite runner and smoke history export so cutover validation can be executed and archived directly from the dashboard.

## Delivered
- Added API endpoint:
  - `POST /api/index.php?action=deployment.smoke.suite` (owner + CSRF)
  - Runs a consolidated smoke suite (public + auth checks) and records both smoke runs automatically.
  - Returns suite result, updated history, and current cutover readiness snapshot.
- Added API endpoint:
  - `GET /api/index.php?action=deployment.smoke.history`
  - Returns smoke run summary, latest run by type, and full smoke run items.
- Added smoke history helper + suite runner logic in API:
  - `deployment_smoke_history_snapshot()`
  - `deployment_smoke_suite_snapshot($note = '')`
- Updated dashboard `Hosting Cutover Toolkit -> Cutover Readiness`:
  - `Run Full Smoke Suite` button
  - `Download Smoke History` button
  - live smoke history panel
- Updated frontend behavior:
  - refresh readiness now also refreshes smoke history
  - manual smoke record actions now refresh smoke history
  - smoke history download exports JSON artifact
- Updated API status phase marker to:
  - `1.28-smoke-suite-and-history`

## Ops Notes
- Use `Run Full Smoke Suite` after deploy verification and before go-live handoff.
- Keep `Download Smoke History` artifact with release/cutover records for audit traceability.
