# Phase 152: App CRM Email Template Preview And Test Send

## Summary
- added app-side CRM email template preview action
- added app-side CRM email template test-send action
- surfaced template preview output in the CRM dashboard

## UI
- added `Preview Template` and `Send Template Test` button handling
- added a dedicated preview output panel for rendered template content
- added test-recipient validation before test-send requests

## API Usage
- uses `crm.email_templates.preview` for rendered subject/body preview
- uses `crm.email_templates.send_test` for bridge-backed SMTP test delivery
- refreshes CRM SMTP summary and watch after test-send actions

## Version
- bumped plugin version to `2.0.0.172`
