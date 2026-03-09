# Phase 126: WebOps Monitor Catalog

## Summary
Started the WebOps stage by adding a monitor catalog and expanding monitor coverage beyond basic uptime and bridge health checks.

## Delivered
- Added API action:
  - `webops.types.list`
- Added WebOps monitor catalog for:
  - `uptime_http`
  - `ssl_expiry`
  - `dns_resolution`
  - `wp_heartbeat`
  - `update_health`
  - `bridge_site_health`
  - `webhook_check`
- Expanded monitor execution to support:
  - SSL expiry inspection
  - DNS resolution checks
  - WordPress heartbeat
  - update health via bridge
- Added plugin bridge endpoints:
  - `lc/v1/bridge/wp-heartbeat`
  - `lc/v1/bridge/update-health`
- Added dashboard controls:
  - `Refresh WebOps Types`
  - `webopsTypes`
- Updated API phase marker:
  - `2.12-webops-monitor-catalog`

## Ops Notes
- `wp_heartbeat` and `update_health` become most useful when a monitor is mapped to a configured bridge site.
