# Phase 74: Release Gate Blocker Classification

## Summary
Release gate watch telemetry now classifies failed checks and highlights baseline-related blockers explicitly.

## Delivered
- Added helper:
  - `deployment_release_gate_failed_items(gate)`
- Extended release gate watch run/state telemetry with:
  - `failed_items`
  - `failed_items_changed`
  - `blocker_flags`
- Added baseline-focused gate-block notification when blockers switch to baseline requirements.
- Added blocker-change audit event:
  - `deployment / release.gate.watch.blockers`
- Updated API phase marker:
  - `1.60-release-gate-blocker-classification`

## Ops Notes
- Operators can now see exactly which release-gate checks are failing without parsing long reason text.
- Baseline blocker flags are available in gate watch runs and state for faster triage.
