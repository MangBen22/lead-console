# Phase 305: Launch Operations Issues Summary

- Added a focused launch operations issues summary that rolls deployment blockers and module issues into one list.
- Added helper:
  - `launch_operations_issues_summary()`
- The issues summary now surfaces:
  - release-gate blocking state
  - cutover readiness blockers or review state
  - watchdog critical or warning state
  - open deployment incidents
  - automation due-now reminders
  - forwarded issues from CRM, Social, WebOps, and SEO operations summaries
- Added API action:
  - `GET /api/index.php?action=launch.operations.issues_summary`
- Added a launch operations issues summary view in the dashboard.
- Updated refresh behavior so the launch snapshot action also refreshes the issues output.

This gives operators one actionable blocker list for launch review instead of forcing them to inspect each module separately.
