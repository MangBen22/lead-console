# Phase 333: Release Decision Latest Compare Export

## Summary
- Added export support for the release-decision latest-compare view.
- Added a dashboard action to download the current compare payload.

## API
- Added `GET /api/index.php?action=deployment.release.decision.latest_compare_export`

## UI
- Added `Download Release Decision Compare` in the release-decision history controls.

## Notes
- Export uses the same latest-vs-previous compare object shown in the dashboard.
