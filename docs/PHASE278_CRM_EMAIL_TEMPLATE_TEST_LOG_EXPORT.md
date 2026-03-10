# Phase 278: CRM Email Template Test Log Export

## Summary
- Added export coverage for CRM email template test logs.

## API
- `crm.email_templates.test_log_export`
  - Supports the same filters as `crm.email_templates.test_log`
  - Optional:
    - `log_id`
    - `detail_site_id`
  - Returns:
    - filtered test-log inventory
    - selected log detail when requested

## UI
- Added a CRM email template test-log export action beside the log filters.
