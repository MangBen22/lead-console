# Phase 134: SEO Extension Sessions

## Summary
Added dashboard-issued SEO extension sessions so extension access can move off a single static key and into revocable session tokens.

## Delivered
- Added storage key:
  - `seo_extension_sessions`
- Added API actions:
  - `seo.extension.sessions.list`
  - `seo.extension.session.create`
  - `seo.extension.session.revoke`
- `seo.extension.intake` now accepts:
  - master ingest key
  - active extension session token
- Extension session usage updates:
  - `last_used_at`
- Added dashboard controls:
  - `seoExtensionSessions`
  - `Create Extension Session`
  - `Revoke Extension Session`
- Updated API phase marker:
  - `2.20-seo-extension-sessions`

## Ops Notes
- Newly created session tokens are returned in the create response; list view intentionally masks stored tokens.
