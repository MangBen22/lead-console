# Phase 322: Release Log Detail Export

- Added export coverage for a single release log entry.
- Added API action:
  - `GET /api/index.php?action=deployment.release.log.detail_export&candidate_id=...`
- Export includes the selected release candidate record.
- Added a release detail download action in the Release Candidate section.
- Updated app wiring so the exported release detail payload is also shown in the release detail view.

This makes one reviewed release candidate easy to archive or share without exporting unrelated log rows.
