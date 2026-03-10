# Phase 198: Social Delivery Watch

## Summary
- added Social delivery watch state and run history
- flags degraded connectors based on retry backlog, rejections, or degraded sync state
- can emit notifications when a manual watch run finds issues

## App API
- added `social.delivery.watch.summary`
- added `social.delivery.watch.run`

## UI
- added:
  - `Refresh Delivery Watch`
  - `Run Delivery Watch`
- added a `Social Delivery Watch` panel in the Social Push Pipeline section

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
