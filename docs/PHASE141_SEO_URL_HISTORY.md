# Phase 141: SEO URL History

## Summary
Added URL-level SEO history so operators can inspect repeated audits for individual pages instead of relying only on project-wide summaries.

## Delivered
- Added API action:
  - `seo.url.history`
- URL history supports:
  - `project_id`
  - optional `url` filter
- URL history output includes:
  - audit count per URL
  - latest score
  - previous score
  - score delta
  - latest priority summary
  - latest page signals
  - source counts
  - timeline for filtered URLs
- Added dashboard controls:
  - `Refresh URL History`
  - `seoHistoryUrlFilter`
  - `seoUrlHistoryView`
- URL history refreshes after:
  - project save
  - project delete
  - audit run
  - extension session create/revoke
- Updated API phase marker:
  - `2.27-seo-url-history`

## Ops Notes
- Use the URL filter when validating changes on a specific service page or landing page.
- Timeline output is especially useful after extension-driven audits because those runs can target non-homepage URLs.
