# Phase 307: Launch Operations Automation History

- Extended automation runs to capture a launch operations snapshot after each automation cycle.
- Updated `execute_automation_run()` to:
  - generate a launch operations snapshot
  - persist that snapshot to launch operations history with `automation_manual` or `automation_scheduler` source
  - attach launch summary fields to the automation run payload
- Launch summary fields now include:
  - `launch_state`
  - `release_gate_allowed`
  - `blocked_modules`
  - `review_modules`
  - `open_incidents`
  - `issue_count`
  - `snapshot_id`
- Updated app automation actions so manual and scheduler runs refresh the launch operations views after completion.

This makes launch operations history reflect real automation activity instead of only manual dashboard refreshes.
