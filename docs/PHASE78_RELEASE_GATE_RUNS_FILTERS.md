# Phase 78: Release Gate Runs Filters

## Summary
Added filterable release-gate run retrieval and dashboard controls so operators can focus on specific blocker patterns quickly.

## Delivered
- Extended endpoint:
  - `GET /api/index.php?action=deployment.release.gate.runs`
  - supports filters:
    - `limit` (1..400)
    - `allowed` (`all|allowed|blocked`)
    - `source` (exact source filter)
    - `failed_item` (exact failed-check item)
- Response now includes:
  - `applied_filters`
  - `total_count`
  - `filtered_total_count`
  - filtered `items` and filtered `summary`
- Added Go-Live filter controls:
  - limit, status, source, failed item
  - `Apply Gate Filters`
  - `Clear Gate Filters`
- Updated API phase marker:
  - `1.64-release-gate-runs-filters`

## Ops Notes
- Use `failed_item=watchdogs_policy_baseline_check_ok_and_fresh` to isolate baseline-check freshness failures.
- Use `source=scheduler_tick_run` to inspect unattended gate behavior separately from manual checks.
