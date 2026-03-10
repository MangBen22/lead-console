# Phase 275: CRM Email Template Export

## Summary
- Added export coverage for CRM email template operations.

## API
- `crm.email_templates.export`
  - Supports the same filters as `crm.email_templates.sites`
  - Optional:
    - `detail_site_id`
    - `test_log_limit`
  - Returns:
    - filtered template site inventory
    - selected site template detail
    - selected site template test log

## UI
- Added a CRM email template export action beside the template site filters.
