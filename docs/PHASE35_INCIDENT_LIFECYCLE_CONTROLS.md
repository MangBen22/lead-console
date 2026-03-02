# Phase 35 Incident Lifecycle Controls

## Delivered
- Added endpoint:
  - `deployment.incident.resolve`
- Added incident report lifecycle fields:
  - `incident_status` (`open`, `resolved`, `reopened`)
  - `incident_updated_at`
  - `incident_updated_by`
  - `incident_note`
- Added Deployment Guard UI controls:
  - report id input
  - status note input
  - `Mark Resolved` button
  - `Reopen Incident` button
  - incident action result viewer

## Behavior
- Incident reports now start as `open`.
- Authorized users can mark reports `resolved` or `reopened`.
- Lifecycle changes emit deployment audit events and notifications.

## Purpose
Supports operational follow-through by tracking incident report closure and re-open actions.
