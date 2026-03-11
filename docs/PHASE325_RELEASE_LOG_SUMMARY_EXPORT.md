# Phase 325: Release Log Summary Export

- Added export coverage for the filtered release log summary.
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.summary_export`
- Export includes:
  - filtered release log summary
  - latest filtered release candidate
- Added a release summary download action in the Release Candidate section.
- Updated app wiring so release-summary exports also refresh the summary view payload in the dashboard.

This makes the release-candidate summary easy to attach to launch review without exporting the entire log.
