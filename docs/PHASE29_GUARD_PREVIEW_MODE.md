# Phase 29 Guard Preview Mode

## Delivered
- Added endpoint:
  - `deployment.guard.preview`
- Added Deployment Guard UI controls:
  - `Preview Guard Result` button
  - preview output panel
- Added shared guard payload collection in frontend to keep save/preview behavior consistent.

## Behavior
- Preview evaluates guard rules (including UTC launch window) without saving settings.
- Returns:
  - `allowed` flag
  - full `reasons` list
  - normalized guard payload snapshot

## Purpose
Lets you test guard configuration safely before applying changes that could block production writes.
