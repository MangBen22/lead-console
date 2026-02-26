# Phase 4 Bridge Sync + CRM Base

## Delivered
- Main app API now supports bridge-aware operations using configured plugin sites.
- Added helper library for:
  - config loading
  - JSON storage
  - secure bridge HTTP calls
- Added bridge route usage in summaries:
  - Leads summary pulls approved lead counts from plugin bridge.
  - CRM summary reads plugin SMTP health from bridge.
- Added CRM connector persistence endpoints:
  - `crm.connectors.list`
  - `crm.connectors.save` (POST JSON)
- Added dashboard panels:
  - Bridge site status
  - CRM connectors list

## Config Needed
Set `plugin_sites` in `app-site/config.php` with each WordPress site URL and bridge key.

## Next
1. Add secure login and role policies for API actions.
2. Add connector CRUD UI forms.
3. Add push pipeline from approved leads to CRM connector targets.
