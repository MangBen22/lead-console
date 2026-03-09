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
25. After sustained healthy watchdog checks, verify auto-resolution entries appear in watchdog runs/state before final cutover approval.
26. Configure `Save Watchdogs Policy` thresholds to match operational tolerance (critical escalation streak and healthy auto-resolve streak).
27. Review `Refresh Watchdogs Policy History` before launch to confirm threshold changes and actor/source audit trail.
28. If needed, use `Restore Watchdogs Policy` (with optional history ID + mode) to roll back thresholds quickly and log the restore action.
29. Use `Preview Restore Diff` before restore actions to confirm threshold deltas and avoid no-op restores.
30. Save a `Policy Baseline` and verify `Refresh Baseline Drift` returns `has_changes=0` before cutover approval.
31. Enable release gate option `Require watchdogs policy baseline match` when you want launch approval to fail on baseline drift.
32. Run `Run Watchdogs Check` and confirm no baseline drift warning notifications are emitted before final launch approval.
33. Run `Run Baseline Check` and verify scheduler status includes `watchdogs_policy_baseline_check_state` and a recent baseline check run.
34. Enable release gate option `Require recent watchdogs baseline check` to enforce fresh baseline validation during launch windows.
35. Policy save/restore and baseline set/clear actions auto-run a baseline check; verify those runs appear in `Refresh Baseline Checks`.
36. In `Refresh Gate Runs`, inspect `failed_items` and `blocker_flags` to quickly identify whether baseline requirements are blocking release.
37. Use gate-runs `summary` (`top_failed_items`, baseline blocker run counts) to prioritize remediation before launch.
38. Use `gate watch summary` panel in Go-Live for quick blocker analytics without scrolling through full runs payloads.
39. Check scheduler status `release_gate_watch_summary` to confirm blocker trends during unattended automation windows.
40. Use Gate Runs filters (`limit`, `status`, `source`, `failed item`) to isolate specific blocker windows and operator actions.
41. Use `Download Gate Runs` after applying filters to archive focused blocker evidence for incident and cutover records.
42. Gate Runs filters persist in browser storage; clear them when switching from deep investigations back to broad launch reviews.
43. Use Gate preset buttons for baseline-match, baseline-check, and signoff-watch blockers to jump directly into common failure categories.
44. Use `Scheduler Gate Summary` panel to monitor gate blocker trends during cron-only windows without opening raw scheduler JSON.
45. Use `Download Blocker Report` to capture gate snapshot + filtered run analytics for escalation or launch review sign-off.
46. Use `gate watch digest` panel for a quick plain-language summary of blocked trends and top failing checks.
47. Gate-watch notifications now include `blocker_digest`; use it for quick triage before opening full run payloads.

## 8) Rollback Plan
1. Restore previous app files.
2. Import most recent backup JSON payload.
3. Re-check health and bridge status.
