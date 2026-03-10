# Phase 154: App CRM Email Template Test Log

## Summary
- added app API access for bridge email template test history
- added CRM dashboard controls to refresh and inspect recent template test sends
- refreshed the log automatically after template save, load, refresh, and test-send actions

## API
- added `crm.email_templates.test_log`
- defaulted to the first configured bridge site when `site_id` is not provided

## UI
- added `Refresh Test Log` to the CRM email template actions
- added a dedicated test-log panel in the CRM email template section

## Version
- bumped plugin version to `2.0.0.174`
