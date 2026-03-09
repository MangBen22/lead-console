# Phase 136: SEO Report Export

## Summary
Added a downloadable SEO report bundle so audit, issue, and extension findings can be exported from the dashboard as a single artifact.

## Delivered
- Added API action:
  - `seo.report.export`
- Added dashboard control:
  - `Download SEO Report`
- Export bundle includes:
  - summary metrics
  - filtered projects
  - latest audits
  - latest extension events
  - priority summary
- Supports optional filtering by current `project_id`
- Updated API phase marker:
  - `2.22-seo-report-export`

## Ops Notes
- Leave the Project ID field blank to export a portfolio-level SEO snapshot.
