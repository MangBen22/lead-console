# Phase 276: CRM Email Template Test Log Filters

## Summary
- Reworked CRM email template test logs into a filtered multi-site workspace.

## API
- `crm.email_templates.test_log`
  - Supports:
    - `site_id`
    - `success`
    - `template_key`
    - `error_code`
    - `search`
    - `page`
    - `limit`

## UI
- Added dedicated test-log filters for site, result, template key, error code, search, page, and limit.
- Test log loading now defaults to the selected template site when the log site filter is empty.
