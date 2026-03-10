# Phase 241: SEO Project Detail

## Summary
- Added a dedicated SEO project detail endpoint and UI panel.
- Project detail now bundles recent audits, extension events, sessions, and top issue rollups.

## API
- `seo.projects.detail`
  - Accepts optional `project_id`
  - Falls back to the first configured project when no ID is supplied
  - Returns:
    - `project`
    - `meta`
    - `latest_audit`
    - `latest_event`
    - `top_issues`
    - `recent_audits`
    - `recent_extension_events`
    - `extension_sessions`

## UI
- Added `Detail Project ID`
- Added `Load Project Detail`
- Added a separate detail view under the SEO project inventory

## Notes
- This gives SEO projects the same inventory/detail split already used in Leads and Social.
