# Phase 327: Release Log Latest Compare Export

- Added export coverage for release log latest compare.
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.latest_compare_export`
- Export includes:
  - latest compare summary
  - latest release candidate
  - previous release candidate
  - metric deltas
- Added a release compare download action in the Release Candidate section.
- Updated app wiring so compare exports also refresh the compare view payload in the dashboard.

This makes release-candidate drift evidence exportable for launch review and escalation.
