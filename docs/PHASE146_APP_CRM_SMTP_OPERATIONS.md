# Phase 146: App CRM SMTP Operations

## Summary
Added app-side CRM SMTP controls so bridge sites can be probed, test-emailed, and confirmed directly from the app dashboard.

## Delivered
- Added API actions:
  - `crm.smtp.summary`
  - `crm.smtp.probe`
  - `crm.smtp.send_test`
  - `crm.smtp.confirm`
- Added reusable CRM SMTP snapshot helper for all configured bridge sites
- `crm.summary` now includes:
  - pending SMTP confirmation site count
  - confirmed SMTP site count
- Added CRM UI panel:
  - `Refresh SMTP Status`
  - `Test Connection`
  - `Send Test Email`
  - `Confirm Received`
  - `Confirm Not Received`
- Added app-side views:
  - `crmSmtpSummary`
  - `crmSmtpResult`
- Updated API phase marker:
  - `2.31-crm-smtp-bridge-ops`

## Ops Notes
- This uses the plugin bridge as the execution layer, so the app remains an orchestrator.
- Site IDs still need to match the bridge configuration in `app-site/config.php`.
