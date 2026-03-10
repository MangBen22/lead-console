# Phase 196: Social Delivery Connector Detail

## Summary
- added per-connector Social delivery detail
- exposes recent sync events and retry items for one social connector
- gives operators a narrower drill-down than the global social delivery summary

## App API
- added `social.delivery.connector_detail`
- requires `connector_id`

## UI
- added connector detail inputs in the Social Push Pipeline section
- added `Refresh Connector Detail`
- added `Social Delivery Connector Detail` output

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
