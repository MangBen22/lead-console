# Phase 31 Bypass Watchdog and Extend

## Delivered
- Added guard bypass watchdog behavior:
  - auto-disables expired emergency bypass
  - sends expiring-soon warning (throttled)
- Added endpoint:
  - `deployment.guard.bypass.extend`
- Added UI control:
  - `Extend Emergency Bypass` button

## Behavior
- Expired bypass is cleared automatically on guard evaluation.
- Expiring-soon alerts are emitted near expiry (with throttling).
- Extend action requires an active bypass and valid reason.
- Extend resets expiry using selected duration (5-240 minutes).

## Audit + Notifications
- Emits audit events:
  - `guard.bypass.auto_disable_expired`
  - `guard.bypass.expiring_soon`
  - `guard.bypass.extend`
- Emits notifications on expiry, warning, and extension.

## Purpose
Improves emergency bypass safety and visibility during high-pressure production incidents.
