# Phase 145: Plugin Bridge SMTP Operations

## Summary
Exposed the plugin’s SMTP probe and test-email workflow through bridge-safe REST routes so the app can drive CRM/email operations without reimplementing mail transport.

## Delivered
- Added bridge routes:
  - `POST /bridge/smtp-probe`
  - `POST /bridge/smtp-send-test`
  - `POST /bridge/smtp-confirm`
- `GET /bridge/smtp-health` now also returns:
  - `test_confirmation`
- Added plugin helpers:
  - `get_smtp_test_confirmation()`
  - `set_smtp_test_confirmation_state()`
- SMTP bridge operations now persist:
  - pending state
  - recipient email
  - send timestamp
  - confirmation timestamp
  - last result
  - last error code
- Updated plugin version:
  - `2.0.0.165`

## Ops Notes
- This phase only exposes the operations over the bridge.
- The next step is surfacing these controls and summaries in the app CRM module.
