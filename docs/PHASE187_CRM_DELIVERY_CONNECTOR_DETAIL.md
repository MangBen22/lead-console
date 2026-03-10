# Phase 187: CRM Delivery Connector Detail

## Summary
- added per-connector CRM delivery detail
- exposes recent sync events and retry items for one connector
- gives operators a narrower drill-down than the global CRM delivery summary

## App API
- added `crm.delivery.connector_detail`
- requires `connector_id`
- returns:
  - masked connector metadata when available
  - connector summary
  - recent sync items
  - recent retry items

## UI
- added connector detail inputs in the CRM Push Pipeline section
- added `Refresh Connector Detail`
- added `Delivery Connector Detail` output below the delivery summary

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
