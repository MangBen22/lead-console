# Phase 280: CRM Operations Snapshot

## Summary
- Added a consolidated CRM operations snapshot across connectors, delivery, retry, SMTP, and email templates.

## API
- `crm.operations.snapshot`
  - Input:
    - `limit`
  - Returns:
    - connector inventory snapshot
    - delivery summary and watch state
    - retry queue snapshot
    - SMTP summary and watch state
    - email template site inventory and test-log snapshot

## UI
- Added a CRM Operations Snapshot section with a refresh action and adjustable snapshot limit.
