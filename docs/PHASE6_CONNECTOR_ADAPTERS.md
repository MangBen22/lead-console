# Phase 6 Connector Adapters (FluentCRM + HubSpot Base)

## Delivered
- Added connector adapter execution layer in main app API.
- Implemented provider paths:
  - `hubspot` + `external_api`
  - `fluentcrm` + `wordpress_plugin`
  - `custom_webhook` + `external_api`
- Added run mode support:
  - `dry_run`
  - `live`
- Added bridge intake endpoint on plugin:
  - `POST /wp-json/lc/v1/bridge/crm-intake`

## Security and UX
- Connector secret fields are masked in connector list responses.
- Connector form now supports:
  - connector ID update/delete
  - site mapping (`site_id`)
  - run mode
  - access token / endpoint / webhook config

## Sync Behavior
- CRM sync now executes against each active connector and stores connector-level results per run in sync log.

## Next
1. Add provider-specific error normalization and retry queue.
2. Add connector test endpoint per provider.
3. Add FluentCRM native API path (if plugin auth keys are available).
