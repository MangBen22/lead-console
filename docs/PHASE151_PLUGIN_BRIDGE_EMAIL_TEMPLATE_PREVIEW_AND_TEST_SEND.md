# Phase 151: Plugin Bridge Email Template Preview and Test Send

## Summary
Added plugin bridge support for previewing rendered email templates and sending template-based test emails through the real WordPress mail pipeline.

## Delivered
- Added bridge routes:
  - `POST /bridge/email-templates/preview`
  - `POST /bridge/email-templates/test-send`
- Added plugin helpers:
  - `get_email_template_preview()`
  - `send_templated_test_email()`
  - `smtp_send_custom_email()`
  - `email_template_preview_vars()`
- Preview responses now include:
  - rendered subject
  - rendered text body
  - rendered HTML body
  - merged sample variables
- Test sends now use the selected saved template and the same placeholder renderer used by production template mail
- Updated plugin version:
  - `2.0.0.171`

## Ops Notes
- Preview and test-send use canonical plugin templates, not a separate app renderer.
- The next step is exposing these preview/send controls in the app CRM email template panel.
