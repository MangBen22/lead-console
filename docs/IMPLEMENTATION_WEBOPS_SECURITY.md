# WebOps Security Module Implementation Spec

## Goals
- Real-time site uptime and security posture monitoring.
- Remote control actions for managed sites.
- Fast incident alerts and auditability.

## Monitor Types
1. `uptime_http`
2. `ssl_expiry`
3. `dns_resolution`
4. `wp_heartbeat` (plugin-managed sites)
5. `update_health`

## Incident Model
- `site_id`
- `severity` (info, warning, critical)
- `type`
- `message`
- `opened_at`
- `resolved_at`
- `context_json`

## Notification Triggers
- Site down / recovered
- SSL expiring threshold crossed
- Failed remote update
- Security anomaly detected

## Remote Actions
- Toggle integration or plugin state
- Run update job
- Rollback to previous known good state

## Safety Controls
- Confirmation step for destructive actions
- Role-based action permissions
- Mandatory action logs
