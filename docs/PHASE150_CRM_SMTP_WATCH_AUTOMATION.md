# Phase 150: CRM SMTP Watch Automation

## Summary
Integrated SMTP watch into CRM execution paths so the watch updates automatically after SMTP actions and during automation runs.

## Delivered
- CRM automation summary now records:
  - `smtp_disconnected_sites`
  - `smtp_watch_run_id`
- CRM automation now runs SMTP watch whenever the CRM module executes
- SMTP action endpoints now return fresh watch results:
  - `crm.smtp.probe`
  - `crm.smtp.send_test`
  - `crm.smtp.confirm`
- CRM app UI now refreshes SMTP watch automatically after SMTP actions
- Updated API phase marker:
  - `2.34-crm-smtp-watch-automation`

## Ops Notes
- SMTP health is now part of operational CRM automation, not a separate manual check.
- Disconnected SMTP sites now contribute to automation issue signaling.
