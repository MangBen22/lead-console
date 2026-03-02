# Phase 30 Emergency Bypass Guard

## Delivered
- Added endpoints:
  - `deployment.guard.bypass.enable`
  - `deployment.guard.bypass.disable`
- Added deployment guard state fields:
  - `emergency_bypass_enabled`
  - `emergency_bypass_expires_at`
  - `emergency_bypass_reason`
  - `emergency_bypass_set_by`
- Added Deployment Guard UI controls:
  - bypass reason input
  - duration input (5-240 minutes)
  - enable/disable bypass buttons

## Behavior
- Emergency bypass temporarily overrides guard blockers while active.
- Requires reason (minimum 8 characters).
- Expires automatically by UTC timestamp.
- Invalid/expired bypass appears in guard reasons.
- Emits audit + notification on enable/disable.

## Purpose
Supports urgent production fixes with strict time limit and full traceability.
