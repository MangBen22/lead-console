# Phase 46: Cutover Evidence Bundle Export

## Summary
Added a single export that captures cutover readiness evidence across smoke checks, release gate state, watch history, pipeline/release records, incidents, and scheduler context.

## Delivered
- Added API endpoint:
  - `GET /api/index.php?action=deployment.cutover.evidence.bundle&note=...`
  - Returns one consolidated JSON bundle with:
    - cutover readiness snapshot
    - release gate snapshot
    - release gate watch state + run history
    - smoke history summary/items
    - cutover pipeline run history
    - release candidate log
    - incident summary + SLA snapshot
    - deployment guard status/reasons
    - automation scheduler status context
- Added dashboard action in `Hosting Cutover Toolkit -> Cutover Readiness`:
  - `Download Cutover Evidence`
- Added audit event:
  - `deployment.cutover.evidence.bundle.export`
- Updated API phase marker:
  - `1.32-cutover-evidence-bundle`

## Ops Notes
- Use this export as the primary cutover evidence artifact for deployment signoff.
- Include a note in the button flow to label why/when the evidence was captured.
