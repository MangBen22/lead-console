# Phase 330: Release Decision History

## Summary
- Added persisted release-decision history.
- Added filtered history and single-snapshot detail endpoints.
- Added a dashboard workspace for reviewing past release decisions.

## API
- Added `GET /api/index.php?action=deployment.release.decision.history`
- Added `GET /api/index.php?action=deployment.release.decision.history.detail`

## Storage
- Added `deployment_release_decision_history.json` in app storage.
- Release decision view and export actions now record snapshots into history.

## UI
- Added release-decision history filters.
- Added release-decision history detail lookup by snapshot ID.
