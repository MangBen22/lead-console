# Phase 323: Release Log Export

- Added export coverage for the filtered release log.
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.export`
- Export includes:
  - filtered release log summary
  - filtered release log items
- Added a release log download action in the Release Candidate section.
- Updated app wiring so release-log exports also refresh the release log view payload in the dashboard.

This makes filtered release-candidate evidence exportable for launch review and handoff.
