# Phase 133: SEO History Summary

## Summary
Added an SEO history-summary layer so operators can quickly see score movement and audit density without reading every audit record.

## Delivered
- Added API action:
  - `seo.history.summary`
- Added dashboard controls:
  - `Refresh SEO History`
  - `seoHistorySummary`
- History summary includes:
  - average score
  - latest score
  - previous score
  - best/worst score
  - trend direction
  - recent score list
  - per-project score stats
- Updated API phase marker:
  - `2.19-seo-history-summary`

## Ops Notes
- Use history summary beside issue summary to distinguish single-audit noise from persistent SEO regression.
