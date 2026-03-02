# Phase 13 Cron Helper + Notification Sound Rules

## Delivered
- Scheduler helper endpoints:
  - `automation.scheduler.status`
  - `automation.scheduler.cron_help`
- Scheduler trigger endpoint enhanced:
  - `automation.scheduler.tick` accepts either owner session or `X-AUTOMATION-KEY`.

## Automation Settings
- Existing automation settings now drive scheduled execution:
  - enabled/disabled
  - interval minutes
  - per-module toggles (CRM, Social, WebOps, SEO)

## Notification Settings
- Added notification settings endpoints:
  - `notifications.settings.get`
  - `notifications.settings.save`
- Added persistent settings:
  - `sound_enabled`
  - `sound_mode` (`critical_only`, `all`, `off`)

## Notification Feed
- `notifications` now returns:
  - unread count
  - critical unread count
  - active notification settings

## Dashboard UI
- Automation Settings panel now includes:
  - scheduler status
  - cron helper output
- Added Notification Settings panel:
  - sound enabled toggle
  - sound mode selector
- Notification sound trigger follows configured mode, with critical-first default.
