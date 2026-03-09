# Phase 140: SEO Action Plan

## Summary
Added a ranked remediation plan so the SEO dashboard can tell operators what to fix next instead of only showing raw audits.

## Delivered
- Added API action:
  - `seo.actions.plan`
- Action plan output includes:
  - `new` issues
  - `persistent` issues
  - `monitor` items for recently cleared issues
  - suggested owner
  - action title
  - recommendation
  - recent audit evidence
- Added dashboard controls:
  - `Refresh Action Plan`
  - `seoActionPlanView`
- Action plan refreshes after:
  - project save
  - project delete
  - audit run
  - extension session create/revoke
- Updated API phase marker:
  - `2.26-seo-action-plan`

## Ops Notes
- The action plan is designed for operator prioritization, not auto-remediation.
- Persistent critical items should be treated as the top queue before content expansion work.
