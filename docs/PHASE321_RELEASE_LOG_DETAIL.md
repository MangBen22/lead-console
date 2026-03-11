# Phase 321: Release Log Detail

- Added detail lookup for individual release log entries.
- Added helper:
  - `deployment_release_log_detail_snapshot()`
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.detail&candidate_id=...`
- Updated the Release Candidate section with a release detail form and result view.
- Added app wiring to load a specific release log entry by candidate ID.

This makes it possible to inspect one release candidate record without scanning the entire release log list.
