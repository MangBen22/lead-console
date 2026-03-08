# Phase 38 Incident SLA Alerting and Query Fix

## Delivered
- Added endpoints:
  - `deployment.incident.sla.check`
  - `deployment.incident.sla.runs`
- Added SLA alert state/history storage:
  - `storage/deployment_incident_sla_state.json`
  - `storage/deployment_incident_sla_runs.json`
- Added incident API query helper in frontend:
  - fixed parameterized GET calls for incident report export and SLA threshold.

## UI Additions
- SLA alert cooldown input
- `Run Incident SLA Check` button
- `Refresh SLA Check Runs` button
- SLA check result viewer
- SLA check runs history viewer

## Behavior
- `deployment.incident.sla.check` computes breaches and applies cooldown to avoid alert spam.
- Sends critical notification when breaches exist and cooldown allows.
- Logs each run with threshold, cooldown, breach count, and alert-sent flag.

## Purpose
Turns SLA checks into an actionable monitored process and fixes incident query parameter handling.
