# Phase 142: SEO Opportunities Summary

## Summary
Added a project-wide SEO opportunities view that highlights recurring issues, the weakest URLs, and pages losing score between audits.

## Delivered
- Added API action:
  - `seo.opportunities.summary`
- Opportunities output includes:
  - recurring issues across audits
  - affected URL count per issue
  - recommended owner and remediation hint
  - lowest-scoring URLs
  - declining URLs based on score delta
- Refactored URL history aggregation into reusable snapshot logic
- Added dashboard controls:
  - `Refresh Opportunities`
  - `seoOpportunitiesView`
- Opportunities refresh after:
  - project save
  - project delete
  - audit run
  - extension session create/revoke
- Updated API phase marker:
  - `2.28-seo-opportunities-summary`

## Ops Notes
- Use recurring issues to decide what should become a template-level fix instead of a one-page fix.
- Use declining URLs as the shortlist for regression review after content or technical changes.
