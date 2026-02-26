# Phase 8 Social Connector Module

## Delivered
- Social connector CRUD endpoints:
  - `social.connectors.list`
  - `social.connectors.save`
  - `social.connectors.delete`
  - `social.connectors.test`
- Social sync pipeline endpoints:
  - `social.push.sync`
  - `social.push.log`
- Social retry queue endpoints:
  - `social.retry.list`
  - `social.retry.run`

## Adapter Paths
- `wordpress_social_bridge` + `wordpress_plugin`
- `social_webhook` + `external_api`

## Dashboard UI
- Social connector form (save/delete/test)
- Social sync controls (run sync/run retry queue)
- Social sync log and retry queue panels

## Plugin Bridge
- Added `POST /wp-json/lc/v1/bridge/social-intake` for draft intake and acceptance tracking.

## Next
1. Add platform-specific capability validation for each social connector.
2. Add per-platform publish windows and scheduling rules.
3. Add inbox sync scaffolding where APIs permit.
