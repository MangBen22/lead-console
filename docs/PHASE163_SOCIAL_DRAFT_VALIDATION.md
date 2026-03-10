# Phase 163: Social Draft Validation

## Summary
- added connector-aware draft validation for Social publishing
- added a Social Draft Validation panel in the dashboard
- surfaced connector issues and warnings before sync and scheduling

## Validation Signals
- `connector_inactive`
- `publish_capability_missing`
- `message_missing`
- `title_missing_for_forum`
- `url_missing_for_video`
- `bridge_site_missing`
- `webhook_missing`

## Version
- bumped plugin version to `2.0.0.183`
