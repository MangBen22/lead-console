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
10. In `Go-Live Status`, configure release gate settings (`freshness window` + required checks, including optional `Require recent cutover signoff`, `Require active signoff integrity valid`, and `Require recent valid signoff integrity watch`), click `Save Release Gate Settings`, then `Refresh Release Gate`.
11. Confirm release gate returns `allowed=true` before generating a release candidate.
12. Run `Run Gate Watch` and archive gate watch runs (`Refresh Gate Runs`) as part of cutover evidence.
13. Click `Download Cutover Evidence` and archive the bundle JSON with deployment records.
14. Click `Create Cutover Signoff` (requires gate allowed), then `Download Latest Signoff` and archive it with the evidence bundle.
15. Run `Verify Latest Signoff` (and optionally `Verify All Signoffs`) to confirm signoff evidence hash integrity before final launch.
16. Use signoff lifecycle controls as needed:
   - `Activate Signoff` to set the release-driving signoff.
   - `Revoke Signoff` (with reason) to invalidate an outdated signoff.
17. Run `Run Signoff Integrity Watch` and archive integrity watch runs (`Refresh Integrity Watch Runs`) as part of cutover evidence.
18. Confirm scheduler status includes both watch states:
   - `release_gate_watch_state`
   - `signoff_integrity_watch_state`
   - `watchdogs_incident_summary`
19. Click `Refresh Watchdogs` in `Go-Live Status` and confirm `deployment.watchdogs.status` is not `critical` before launch.
20. Run `Run Full Cutover Check` and confirm pipeline summary includes `watchdogs_status` not `critical`.
21. Run `Run Watchdogs Check` and `Refresh Watchdogs Runs` to verify watchdog check execution is logged with current status.
22. If watchdog checks remain `critical`, review `Incident Reports` for auto-created watchdog incidents and resolve root cause before cutover.
23. Use `Refresh Watchdogs Incident` plus `Resolve/Reopen Watchdogs Incident` quick actions in Go-Live after remediation, and include an operator note before actioning.
24. Use `Refresh Watchdogs Incident Summary` to confirm watchdog incident counts and latest open state are back within expected levels.

## 8) Rollback Plan
1. Restore previous app files.
2. Import most recent backup JSON payload.
3. Re-check health and bridge status.
