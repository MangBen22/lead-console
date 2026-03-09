# Phase 138: SEO Latest Compare

## Summary
Added a latest-audit comparison view so operators can see what changed between the two most recent audits for a project.

## Delivered
- Added API action:
  - `seo.compare.latest`
- Added dashboard controls:
  - `Refresh Audit Compare`
  - `seoCompareView`
- Comparison output includes:
  - latest audit
  - previous audit
  - score delta
  - added checks
  - cleared checks
  - changed priorities
- Uses current `seoProjectId` when provided
- Updated API phase marker:
  - `2.24-seo-latest-compare`

## Ops Notes
- Run a fresh audit and compare immediately after remediation to confirm which issues actually cleared.
