# Phase 86: Release Gate Summary Ratios

## Summary
Extended gate-run analytics with percentage metrics to make blocker intensity easier to evaluate.

## Delivered
- Added ratio fields to `deployment_release_gate_runs_summary()`:
  - `blocked_ratio_percent`
  - `allowed_ratio_percent`
  - `baseline_match_blocked_share_percent`
  - `baseline_check_blocked_share_percent`
  - `signoff_integrity_watch_blocked_share_percent`
- Updated gate digest rendering to include ratio lines.
- Updated API phase marker:
  - `1.72-release-gate-summary-ratios`

## Ops Notes
- Ratio fields are especially useful when comparing filtered windows with different run counts.
- Blocker share percentages are calculated against blocked runs, not total runs.
