# Phase 272: CRM SMTP Detail

## Summary
- Added a dedicated CRM SMTP detail view per bridge site.

## API
- `crm.smtp.detail`
  - Inputs:
    - `site_id`
    - `limit`
  - Returns:
    - site info
    - SMTP health
    - test confirmation state
    - persisted SMTP watch state
    - recent SMTP watch runs for the site

## UI
- Added SMTP detail controls for site ID and watch-run limit.
- Loading SMTP detail backfills the SMTP action form and CRM email template site ID.
