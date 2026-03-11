# Phase 328: Release Decision Snapshot

- Added a consolidated release decision snapshot.
- Added helper:
  - `deployment_release_decision_snapshot()`
- Decision logic combines:
  - release gate state
  - cutover readiness
  - launch operations state
  - latest release candidate
  - active signoff presence
- Decision returns one of:
  - `launch`
  - `review`
  - `hold`
- Added API action:
  - `GET /api/index.php?action=deployment.release.decision`
- Updated the Release Candidate section with a release decision panel and refresh action.
- Release candidate generation response now includes launch operations context and launch issues.

This creates a single operator-facing release recommendation instead of requiring manual reconciliation across several panels.
