# Phase 12 Automation Scheduler + Module Settings

## Delivered
- Automation settings endpoints:
  - `automation.settings.get`
  - `automation.settings.save`
- Scheduler trigger endpoint:
  - `automation.scheduler.tick`

## Scheduler Auth
- Accepts either:
  - authenticated owner session, or
  - `X-AUTOMATION-KEY` matching `automation_scheduler_key` in config.

## Scheduler Behavior
- Honors `enabled` flag and `interval_minutes`.
- Skips run when interval is not yet reached.
- Stores `last_run_at` after successful scheduled/manual runs.

## Module Toggles
Automation settings can enable/disable each module independently:
- CRM
- Social
- WebOps
- SEO

## Dashboard UI
- Added Automation Settings form with:
  - enabled toggle
  - interval input
  - module on/off controls
- Added `Run Scheduler Tick (Test)` action in Automation Runner.

## Next
1. Add host-level cron example scripts for Linux/Hostinger.
2. Add run duration and timeout limits per module.
3. Add escalation notification if scheduler has repeated skipped/failed runs.
