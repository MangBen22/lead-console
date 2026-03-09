# Phase 49: Cutover Signoff Integrity Verification

## Summary
Added integrity verification for cutover signoffs so operators can prove signoff payloads were not altered after approval.

## Delivered
- Signoff records now embed evidence bundle payload at creation time.
- Added verification functions:
  - latest/by-id signoff verification (`hash + bundle ID checks`)
  - bulk verification across signoff history
- Added API endpoints:
  - `GET /api/index.php?action=deployment.cutover.signoff.verify[&signoff_id=...]`
  - `GET /api/index.php?action=deployment.cutover.signoff.verify_all&limit=...`
- Added dashboard controls in `Cutover Readiness`:
  - `Verify Latest Signoff`
  - `Verify All Signoffs`
  - verification result panel
- Updated API phase marker:
  - `1.35-cutover-signoff-integrity`

## Ops Notes
- Legacy signoffs created before this phase are marked as unverifiable because they do not contain embedded evidence payloads.
- New signoffs are verifiable with:
  - `bundle_id_match = 1`
  - `hash_match = 1`
