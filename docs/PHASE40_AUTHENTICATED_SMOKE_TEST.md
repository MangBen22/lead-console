# Phase 40: Authenticated Smoke Test Script

## Summary
Added an authenticated smoke test script for `app-site` so deployment validation can verify protected API routes, session login, and CSRF-backed POST behavior.

## Delivered
- Added `scripts/smoke-auth.ps1` that:
  - logs in through `/` using configured credentials,
  - captures session cookies,
  - extracts `window.appCsrfToken` from dashboard HTML,
  - validates authenticated API endpoints:
    - `deployment.guard.status`
    - `deployment.pipeline.runs`
    - `deployment.incident.reports`
    - `deployment.incident.summary`
    - `deployment.incident.sla.runs`
  - performs a safe CSRF-protected POST check:
    - `deployment.guard.preview`
  - returns a clear pass/fail summary per endpoint.

## Run
From repo root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\smoke-auth.ps1 -BaseUrl https://app.5n2digital.com -Email owner@yourdomain.com -Password "your-strong-password"
```

## Ops Notes
- This script is intended for post-deploy verification and incident triage.
- It does not mutate durable production state; `deployment.guard.preview` is a dry-run style validation endpoint.
