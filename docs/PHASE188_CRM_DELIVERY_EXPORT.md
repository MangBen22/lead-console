# Phase 188: CRM Delivery Export

## Summary
- added export support for CRM delivery diagnostics
- exports the CRM delivery summary and optionally one connector detail view
- added audit logging for CRM delivery exports

## App UI
- added `Download Delivery Export` in the CRM Push Pipeline section
- export uses the connector detail inputs when a connector id is present

## App API
- added `crm.delivery.export`
- returns:
  - export timestamp
  - CRM delivery summary snapshot
  - optional connector detail snapshot

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
