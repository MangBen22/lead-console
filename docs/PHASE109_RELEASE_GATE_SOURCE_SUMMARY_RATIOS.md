# Phase 109: Release Gate Source Summary Ratios

## Summary
Added scheduler/manual source counters and blocked ratios to Gate Runs summary analytics.

## Delivered
- Extended runs summary with:
  - `scheduler_runs`
  - `manual_runs`
  - `scheduler_blocked_runs`
  - `manual_blocked_runs`
  - `scheduler_blocked_ratio_percent`
  - `manual_blocked_ratio_percent`
- Updated Gate Runs digest rendering with source counter and ratio lines.
- Updated API phase marker:
  - `1.95-release-gate-source-summary-ratios`

## Ops Notes
- Compare scheduler vs manual blocked ratios to detect automation-only drift or operator-only issues.
