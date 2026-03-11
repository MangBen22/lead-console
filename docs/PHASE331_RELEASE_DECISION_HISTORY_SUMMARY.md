# Phase 331: Release Decision History Summary

## Summary
- Added aggregate summary reporting for recent release-decision history.
- Added decision, launch-state, average, and delta rollups for operator review.

## API
- Added `GET /api/index.php?action=deployment.release.decision.history_summary`

## UI
- Added a release-decision history summary panel under the Release Candidate workspace.

## Notes
- Summary uses the latest history window and reports change between the newest and oldest snapshot in that window.
