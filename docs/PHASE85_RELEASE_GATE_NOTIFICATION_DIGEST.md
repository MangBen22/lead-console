# Phase 85: Release Gate Notification Digest

## Summary
Added concise blocker digest strings to release-gate watch telemetry and notifications for faster alert triage.

## Delivered
- Release gate watch now computes `blocker_digest` from failed check items.
- Added `blocker_digest` to:
  - gate watch run payloads
  - gate watch state (`last_blocker_digest`)
  - gate watch alert notification metadata
- Baseline blocker warning notifications also include `blocker_digest`.
- Updated API phase marker:
  - `1.71-release-gate-notification-digest`

## Ops Notes
- `blocker_digest` is optimized for quick reading in notifications and logs.
- Full failed-item detail remains available in run payloads and summaries.
