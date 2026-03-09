# Phase 135: SEO Extension Summary

## Summary
Added an extension-summary layer so browser extension findings can be reviewed as trends and recurring issue groups instead of only raw event payloads.

## Delivered
- Added API action:
  - `seo.extension.events.summary`
- Extension intake now stores:
  - `priority_summary`
  - `issue_rollup`
- Added dashboard controls:
  - `Refresh Extension Summary`
  - `seoExtensionSummary`
- Extension summary includes:
  - event count
  - average score
  - per-project event stats
  - top recurring issue checks
  - latest event snapshot
- Updated API phase marker:
  - `2.21-seo-extension-summary`

## Ops Notes
- Use extension summary to identify recurring page-level problems before converting them into broader project audits.
