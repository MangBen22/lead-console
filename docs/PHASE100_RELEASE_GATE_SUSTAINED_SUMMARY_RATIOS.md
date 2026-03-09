# Phase 100: Release Gate Sustained Summary Ratios

## Summary
Extended Gate Runs analytics with sustained counters and ratios, and exposed them in digest output for faster trend severity assessment.

## Delivered
- Added summary counters:
  - `sustained_active_runs`
  - `sustained_clear_runs`
  - `sustained_alert_sent_runs`
- Added summary ratios:
  - `sustained_active_ratio_percent`
  - `sustained_alert_sent_ratio_percent`
  - `status_changed_ratio_percent`
- Updated Gate Runs digest rendering to show sustained counters and ratio lines.
- Updated API phase marker:
  - `1.86-release-gate-sustained-summary-ratios`

## Ops Notes
- High `sustained_active_ratio_percent` plus high `sustained_alert_sent_ratio_percent` indicates persistent, noisy blockers likely requiring escalation.
