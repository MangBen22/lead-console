# Phase 122: Social Drafts Preview

## Summary
Added a Social draft preview endpoint so operators can see current publish candidates and connector readiness before triggering a sync.

## Delivered
- Added API action:
  - `social.drafts.preview`
- Added connector readiness helper covering:
  - inactive connectors
  - missing publish capability
  - missing bridge site mapping
  - missing webhook URL
- Added dashboard controls:
  - `Refresh Social Drafts`
  - `socialDraftsPreview`
- Social connector save/delete flows now refresh draft preview automatically.
- Updated API phase marker:
  - `2.08-social-drafts-preview`

## Ops Notes
- Use draft preview before live sync to identify connectors that are configured but not publish-ready.
