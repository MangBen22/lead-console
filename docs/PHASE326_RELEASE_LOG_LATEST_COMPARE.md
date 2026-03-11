# Phase 326: Release Log Latest Compare

- Added latest-vs-previous compare support for release log entries.
- Added helper:
  - `deployment_release_log_latest_compare()`
- Compare output reports:
  - latest and previous candidate IDs
  - latest and previous candidate status
  - latest and previous launch state
  - deltas for gate, guard, launch issue count, and launch module counts
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.latest_compare`
- Added a latest compare view in the Release Candidate section.
- Updated app wiring so release latest compare loads alongside the release log and summary.

This gives operators a direct read on how the newest release candidate differs from the previous one.
