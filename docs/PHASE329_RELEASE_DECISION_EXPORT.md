# Phase 329: Release Decision Export

## Summary
- Added export support for the release decision snapshot.
- Added a dashboard action to download the current release decision payload.

## API
- Added `GET /api/index.php?action=deployment.release.decision.export`
  - Accepts optional `freshness_minutes`
  - Returns `filename`, `export`, and `time`

## UI
- Added `Download Release Decision` in the Release Candidate panel.

## Notes
- Export uses the same decision snapshot logic as the live release decision view.
- The export response mirrors the on-screen decision object so review artifacts stay portable.
