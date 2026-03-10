# Phase 285: CRM Operations Issues Summary

## Summary
- Added a focused CRM operations issues summary.

## API
- `crm.operations.issues_summary`
  - Input:
    - `limit`
  - Returns:
    - severity counts
    - current CRM operations issues across SMTP, delivery, retry, connectors, and email template tests

## UI
- Added a CRM operations issues summary view under the CRM operations section.
