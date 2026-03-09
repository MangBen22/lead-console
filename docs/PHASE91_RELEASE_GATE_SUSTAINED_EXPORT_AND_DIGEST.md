# Phase 91: Release Gate Sustained Export and Digest

## Summary
Extended sustained-blocked telemetry beyond raw API responses so operators can see and archive trend state directly in gate exports and digest views.

## Delivered
- Added `state` and `sustained_state` fields to `deployment.release.gate.runs.export`.
- Updated scheduler gate summary payload rendering to include `release_gate_sustained_digest`.
- Updated Gate Runs summary/digest rendering to include `sustained_state` and a readable sustained-trend line.
- Updated API phase marker:
  - `1.77-release-gate-sustained-export-digest`

## Ops Notes
- Escalation exports now carry sustained trend metadata for post-incident analysis.
- Gate digest includes sustained trend state (`active|clear`), ratio, window size, and last change/alert timestamps for faster triage.
