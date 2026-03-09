# Phase 137: SEO Project Snapshot

## Summary
Added a project snapshot view so one SEO project can be reviewed as a single working unit instead of manually correlating multiple panels.

## Delivered
- Added API action:
  - `seo.project.snapshot`
- Added dashboard controls:
  - `Refresh Project Snapshot`
  - `seoProjectSnapshot`
- Project snapshot includes:
  - selected project
  - audit count
  - extension event count
  - extension session count
  - average score
  - issue summary
  - latest audit
  - latest extension event
- Uses current `seoProjectId` when provided, otherwise falls back to the first available project
- Updated API phase marker:
  - `2.23-seo-project-snapshot`

## Ops Notes
- Set the Project ID field before refreshing if you want the snapshot locked to a specific project.
