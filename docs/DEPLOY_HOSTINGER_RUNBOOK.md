# Hostinger Deployment Runbook (app.5n2digital.com)

## 1) Prepare Branch and Backup
1. Confirm branch is `app`.
2. Export backup from dashboard (`Backup + Audit -> Export Backup`).
3. Save backup JSON off-server.

## 2) Upload App Files
1. Deploy repository contents to hosting target.
2. Ensure `app-site/` is the web root for `app.5n2digital.com` (or mapped subfolder).
3. Verify PHP version is compatible (`>= 7.4`, recommended `8.1+`).

## 3) Configure Production Secrets
1. Copy `app-site/config.production.example.php` to `app-site/config.php`.
2. Replace all placeholder values:
   - `session_key`
   - `demo_admin_email`
   - `demo_admin_password`
   - `automation_scheduler_key`
   - `seo_extension_ingest_key`
   - `plugin_sites[*].bridge_key`
3. Remove any test credentials.

## 4) Bridge Pairing
1. In each WordPress plugin site:
   - Enable `Plugin bridge API`
   - Set `Bridge shared key`
2. Mirror each site in `config.php -> plugin_sites`.
3. Confirm with:
   - `GET /api/index.php?action=bridge.sites`

## 5) Health Validation
1. Check:
   - `GET /api/index.php?action=healthcheck`
2. Must be `ok: true` or `status: warning` only for non-critical missing setup.
3. Fix any `critical` check before launch.

## 6) Scheduler Setup
1. Retrieve helper command:
   - `GET /api/index.php?action=automation.scheduler.cron_help`
2. Add Hostinger cron job using returned Linux example.
3. Set desired interval in dashboard `Automation Settings`.
4. Validate with:
   - `Run Scheduler Tick (Test)` in dashboard.

## 7) Final Smoke Test
1. Run all automations once manually.
2. Verify notifications, audit logs, and module summaries update.
3. Verify `backup.export` and `backup.import` both work.
4. Run deployment public-endpoint smoke test from repo root:
   - `powershell -ExecutionPolicy Bypass -File scripts\smoke-deploy.ps1 -BaseUrl https://app.5n2digital.com`
5. Run authenticated smoke test from repo root:
   - `powershell -ExecutionPolicy Bypass -File scripts\smoke-auth.ps1 -BaseUrl https://app.5n2digital.com -Email owner@yourdomain.com -Password "your-strong-password"`
6. Confirm all authenticated checks return `ok=True` and no endpoint reports HTTP 5xx.
7. In dashboard `Hosting Cutover Toolkit -> Cutover Readiness`, record the public smoke result and confirm readiness state moves to `ready` (or shows exact blockers/warnings).
8. Click `Run Full Smoke Suite` to record both public+auth smoke snapshots in one action.
9. Click `Download Smoke History` and archive the JSON with deployment artifacts.
10. In `Go-Live Status`, set release gate freshness window (recommended 30 minutes), click `Refresh Release Gate`, and confirm gate `allowed=true` before generating a release candidate.

## 8) Rollback Plan
1. Restore previous app files.
2. Import most recent backup JSON payload.
3. Re-check health and bridge status.
