# Phase 273: CRM SMTP Export

## Summary
- Added export coverage for filtered CRM SMTP operations with optional site detail.

## API
- `crm.smtp.export`
  - Supports the same filters as `crm.smtp.summary`
  - Optional:
    - `detail_site_id`
    - `detail_limit`
  - Returns:
    - filtered SMTP summary
    - SMTP watch state and runs
    - site detail when requested

## UI
- Added a CRM SMTP export action beside the SMTP filters.
- Export includes the selected SMTP detail site when present.
