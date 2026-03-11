# Phase 339: Release Decision Issues Summary

## Summary
- Added an issue rollup for the release-decision snapshot.
- Added a dashboard view that turns failed decision checks into a focused blocker list.

## API
- Added `GET /api/index.php?action=deployment.release.decision.issues_summary`

## UI
- Added a release-decision issues summary panel in the Release Candidate workspace.

## Notes
- The issue rollup is built from failed release-decision checks and keeps the underlying decision snapshot attached for review.
