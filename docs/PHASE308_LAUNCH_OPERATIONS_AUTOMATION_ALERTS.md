# Phase 308: Launch Operations Automation Alerts

- Added launch-state alerting for automation-driven launch operations changes.
- Added helpers:
  - `launch_operations_automation_state_path()`
  - `launch_operations_automation_alert()`
- Automation launch alerts now:
  - send `critical` when launch state becomes blocked or blocked issue count worsens
  - send `warning` when launch state requires review after being ready or when review issue count changes
  - send `success` when launch state recovers to ready
- Persisted launch automation alert state to avoid repeating the same notification every run.
- Extended automation run payloads with `launch.automation_alert` metadata.

This turns scheduled automation into an active launch-state monitor instead of only a passive recorder.
