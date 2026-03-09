# Phase 41: Cutover Readiness Card and Smoke Run Tracking

## Summary
Added a dedicated cutover readiness card in the app dashboard so deployment status is visible in one place, including smoke checks, guard state, incident pressure, and latest pipeline run.

## Delivered
- Added API endpoint:
  - `GET /api/index.php?action=deployment.cutover.readiness`
  - Returns a consolidated readiness snapshot with:
    - install/preflight/go-live/guard states
    - latest pipeline run
    - latest smoke runs by type (`public`, `auth`)
    - open incident count + SLA breach count
    - normalized checklist + computed status (`ready`, `review_required`, `blocked`)
- Added API endpoint:
  - `POST /api/index.php?action=deployment.smoke.report` (owner + CSRF)
  - Records smoke run status (`public` or `auth`) with note/details.
  - Triggers audit event + notification.
- Added storage file support:
  - `app-site/storage/deployment_smoke_runs.json` (created automatically at runtime)
- Updated dashboard UI in Hosting Cutover Toolkit:
  - new `Cutover Readiness` panel
  - refresh button
  - manual buttons to record pass/fail for public and auth smoke checks
  - smoke note input and action result view
- Updated `scripts/smoke-auth.ps1`:
  - after endpoint checks, it now reports result to `deployment.smoke.report` automatically.

## Operational Notes
- Public smoke script (`smoke-deploy.ps1`) still validates public endpoints only; record that result in dashboard so readiness reflects it.
- Auth smoke script (`smoke-auth.ps1`) now auto-records its own run in readiness history.
