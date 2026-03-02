# Phase 25 Artifact Verify Workflow

## Delivered
- Added endpoint:
  - `deployment.artifact.verify`
- Added Release Candidate UI controls:
  - baseline manifest JSON input
  - `Verify Uploaded Artifacts` button
  - verification result viewer

## Verification Behavior
- Compares baseline manifest items against current server manifest.
- Reports:
  - missing files on server
  - new files on server
  - checksum/size/existence mismatches
- Result status:
  - `match` (HTTP 200)
  - `mismatch` (HTTP 409)
- Emits notification and audit event:
  - `deployment.artifact.verify`

## Purpose
Gives a direct post-upload integrity check before go-live approval.
