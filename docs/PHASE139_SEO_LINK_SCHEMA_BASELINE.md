# Phase 139: SEO Link and Schema Baseline

## Summary
Expanded the SEO audit baseline so homepage audits and extension intake carry stronger crawl signals instead of only basic meta checks.

## Delivered
- Added richer `page_signals` defaults and normalization for:
  - heading counts
  - visible word count
  - internal link count
  - external link count
  - unique external hosts
  - schema count
  - schema types
- Internal SEO audit now checks:
  - H2 coverage
  - thin content
  - internal link coverage
  - structured data presence
  - multiple H1 warnings
- Browser extension intake now stores normalized `page_signals`
- Extension-generated audits now persist `page_signals`
- Updated API phase marker:
  - `2.25-seo-link-schema-baseline`

## Ops Notes
- Extension clients can now send richer page diagnostics through `page_signals` without requiring a separate schema.
- Audits now surface crawl-structure problems earlier, which makes later remediation planning more useful.
