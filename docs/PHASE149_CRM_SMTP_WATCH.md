# Phase 149: CRM SMTP Watch

## Summary
Added app-side SMTP watch state and run history so bridge site mail health can be tracked over time with change-based notifications.

## Delivered
- Added storage:
  - `crm_smtp_watch_state`
  - `crm_smtp_watch_runs`
- Added helper:
  - `crm_smtp_watch_snapshot()`
- Added API actions:
  - `crm.smtp.watch.summary`
  - `crm.smtp.watch.run`
- SMTP watch tracks:
  - disconnected sites
  - pending confirmations
  - confirmed sites
  - bridge error sites
  - changed sites
- Notifications fire on:
  - SMTP disconnection
  - SMTP recovery
  - new pending confirmation
  - new confirmed state
- Added CRM UI controls:
  - `Refresh SMTP Watch`
  - `Run SMTP Watch`
  - `crmSmtpWatchView`
- Updated API phase marker:
  - `2.33-crm-smtp-watch`

## Ops Notes
- This phase makes SMTP health historical instead of purely point-in-time.
- The next step is wiring the watch into automation and post-action refresh paths.
