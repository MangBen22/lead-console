# Phase 147: Plugin Bridge Email Templates

## Summary
Exposed plugin email templates over the bridge so the app can read and update canonical template content without creating a separate template store.

## Delivered
- Added bridge routes:
  - `GET /bridge/email-templates`
  - `POST /bridge/email-templates`
- Added plugin helpers:
  - `get_email_templates_snapshot()`
  - `save_email_templates_snapshot()`
  - `email_template_definitions()`
  - `email_template_placeholders()`
- Template bridge responses now include:
  - template labels
  - subject/body values
  - placeholder hints
- Bridge saves reuse the existing plugin settings sanitizer
- Updated plugin version:
  - `2.0.0.167`

## Ops Notes
- This keeps WordPress as the source of truth for email templates.
- The next step is app-side template loading and saving per bridge site.
