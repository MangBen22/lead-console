# Phase 52: Cutover Signoff Integrity Watchdog

## Summary
Added an integrity watchdog for the active cutover signoff, with manual run controls and scheduler integration.

## Delivered
- Added signoff integrity watchdog storage:
  - `deployment_cutover_signoff_integrity_state`
  - `deployment_cutover_signoff_integrity_runs`
- Added watchdog snapshot engine:
  - `deployment_cutover_signoff_integrity_watch_snapshot(source)`
  - evaluates active signoff integrity status: `valid`, `invalid`, `unverifiable`, `no_active`
  - emits transition/reminder notifications with cooldown
- Added API endpoints:
  - `POST /api/index.php?action=deployment.cutover.signoff.integrity.watch`
  - `GET /api/index.php?action=deployment.cutover.signoff.integrity.runs`
- Scheduler integration:
  - `automation.scheduler.tick` now runs signoff integrity watch for:
    - disabled skip
    - interval skip
    - normal scheduler run
  - `automation.scheduler.status` now includes:
    - `signoff_integrity_watch_state`
    - `signoff_integrity_watch_last_run`
- Dashboard updates (`Hosting Cutover Toolkit`):
  - `Run Signoff Integrity Watch`
  - `Refresh Integrity Watch Runs`
  - new views for latest watch result + runs/state
- Evidence bundle update:
  - includes `signoff_integrity_watch` state and run history
- Updated API phase marker:
  - `1.38-signoff-integrity-watchdog`

## Ops Notes
- Keep integrity watch active during cutover windows.
- If status is `invalid`, revoke and recreate/activate a fresh signoff before launch.
