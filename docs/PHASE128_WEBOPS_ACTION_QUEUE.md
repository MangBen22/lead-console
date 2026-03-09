# Phase 128: WebOps Action Queue

## Summary
Added queued WebOps action intake so remote maintenance tasks can be staged, reviewed, and executed through a controlled queue instead of direct ad hoc requests.

## Delivered
- Added API actions:
  - `webops.actions.list`
  - `webops.actions.enqueue`
- Added storage key:
  - `webops_actions_queue`
- WebOps summary now reports:
  - `queued_actions`
- Added dashboard controls:
  - `Refresh WebOps Actions`
  - `webopsActionsQueue`
  - `Queue WebOps Action`
- Supported queued action metadata:
  - `site_id`
  - `action_type`
  - `plugin_file`
  - `desired_state`
  - `run_mode`
- Updated API phase marker:
  - `2.14-webops-action-queue`

## Ops Notes
- Actions are queued only in this phase; execution remains a separate, auditable step.
