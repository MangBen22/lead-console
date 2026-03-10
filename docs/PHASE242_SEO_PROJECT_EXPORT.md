# Phase 242: SEO Project Export

## Summary
- Added export support for the filtered SEO project inventory.
- Export can optionally include the currently selected project detail payload.

## API
- `seo.projects.export`
  - Accepts the same filters as `seo.projects.list`
  - Accepts `project_id`
  - Accepts `include_detail`

## UI
- Added `Download SEO Projects`
- Export respects the active project filters
- If a detail project ID is loaded, the export also includes that project detail snapshot

## Notes
- This keeps SEO exports consistent with the workspace-style exports already used in Leads, CRM, and Social.
