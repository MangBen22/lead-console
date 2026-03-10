# Phase 277: CRM Email Template Test Log Detail

## Summary
- Added detailed lookup for individual CRM email template test-log entries.

## API
- `crm.email_templates.test_log_detail`
  - Inputs:
    - `log_id`
    - `site_id` (optional)
  - Returns:
    - test-log item
    - site info
    - current template payload for the same template key

## UI
- Added a template test-log detail form by log ID.
- Loading log detail backfills the selected template site and template key.
