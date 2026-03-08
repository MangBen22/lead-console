# Phase 37 Incident SLA Monitor

## Delivered
- Added endpoint:
  - `deployment.incident.sla`
- Added Deployment Guard UI controls:
  - SLA threshold minutes input
  - `Refresh Incident SLA` button
  - incident SLA result viewer

## SLA Logic
- Evaluates incidents in status:
  - `open`
  - `reopened`
- Flags breaches when incident age in minutes exceeds threshold.
- Threshold range:
  - minimum `5`
  - maximum `10080` (7 days)

## Purpose
Surfaces aged unresolved incidents so teams can prioritize follow-up and close operational risk faster.
