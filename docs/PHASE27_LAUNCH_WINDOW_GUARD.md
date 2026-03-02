# Phase 27 Launch Window Guard

## Delivered
- Added deployment guard launch window fields:
  - `launch_window_enabled`
  - `launch_window_start`
  - `launch_window_end`
- Added guard enforcement logic:
  - blocks risky writes when current time is outside configured window
  - blocks when launch window values are invalid
- Added dashboard controls in Deployment Guard:
  - enable launch window checkbox
  - start/end datetime inputs (UTC)

## Behavior
- Launch window only applies when enabled.
- Guard reasons now include launch-window failures.
- Guard save audit log now records launch-window settings.

## Purpose
Limits production write operations to approved launch windows for safer cutovers.
