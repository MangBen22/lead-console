# Phase 132: SEO Issues Summary

## Summary
Added an SEO issue-summary layer so audit data can be reviewed as prioritized fixes instead of only raw per-audit checks.

## Delivered
- Added API action:
  - `seo.issues.summary`
- Added issue rollup helpers for:
  - `critical`
  - `fix_soon`
  - `nice_to_have`
- Internal and extension-sourced audits now store:
  - `priority_summary`
  - `issue_rollup`
- Added dashboard controls:
  - `Refresh SEO Issues`
  - `seoIssuesSummary`
- Updated API phase marker:
  - `2.18-seo-issues-summary`

## Ops Notes
- Use issue summary as the default triage view before drilling into individual audit payloads.
