# Phase 2 Plugin Bridge (Initial)

## Delivered
- REST bridge endpoints in plugin:
  - `GET /wp-json/lc/v1/bridge/status`
  - `POST /wp-json/lc/v1/bridge/push-approved`
- Shared-key header auth: `X-LC-Bridge-Key`
- Settings fields:
  - `Plugin bridge API` (enable/disable)
  - `Bridge shared key`
- Bridge sync request log entry in system logs.

## Purpose
This creates the first secure bridge between the main app website and each WordPress plugin install.

## Next
1. Add site registration/pairing flow.
2. Add signed request replay protection.
3. Add outbound webhook from plugin to app for push events.
