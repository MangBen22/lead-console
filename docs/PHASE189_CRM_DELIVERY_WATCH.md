# Phase 189: CRM Delivery Watch

## Summary
- added CRM delivery watch state and run history
- flags degraded connectors based on retry backlog, rejections, or degraded sync state
- can emit notifications when a manual watch run finds issues

## App API
- added `crm.delivery.watch.summary`
- added `crm.delivery.watch.run`
- stores latest watch state and recent watch runs in app storage

## UI
- added:
  - `Refresh Delivery Watch`
  - `Run Delivery Watch`
- added a `Delivery Watch` panel in the CRM Push Pipeline section
- CRM sync and retry actions now refresh the watch automatically

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
