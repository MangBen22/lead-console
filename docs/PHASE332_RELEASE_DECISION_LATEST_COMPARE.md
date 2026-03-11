# Phase 332: Release Decision Latest Compare

## Summary
- Added latest-versus-previous comparison for release-decision history.
- Added delta reporting for critical failures, warning failures, release-gate allowance, and active signoff presence.

## API
- Added `GET /api/index.php?action=deployment.release.decision.latest_compare`

## UI
- Added a release-decision compare panel in the Release Candidate workspace.

## Notes
- Compare uses the two most recent release-decision history entries.
