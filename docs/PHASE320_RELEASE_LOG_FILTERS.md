# Phase 320: Release Log Filters

- Added filtered release log listing.
- Added helpers:
  - `deployment_release_log_apply_filters()`
  - `deployment_release_log_list_snapshot()`
- Release log now supports:
  - `status`
  - `launch_state`
  - `search`
  - `page`
  - `limit`
- Updated API action:
  - `deployment.release.log`
- Updated the Release Candidate section with release-log filter controls.
- Updated app wiring so release log loading uses the current filter state.

This turns the release log into a usable workspace now that candidates contain launch-state data.
