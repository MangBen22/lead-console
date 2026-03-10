# Phase 171: Social Draft Delivery Plan

## What changed
- Added `social_draft_delivery_plan()` to map current drafts against active Social connectors.
- Added `social.drafts.plan` for operator-facing draft-to-connector readiness planning.
- Added a Social Draft Delivery Plan panel in the app UI.
- Extended Social operations snapshot with draft-ready and draft-blocked pair counts.
- Refreshed the plan automatically after Social connector save and delete actions.

## Why it matters
- Operators can now see which draft/connector combinations are ready before running publish or retry flows.
- Social operations snapshot exposes publish readiness in a concrete way, not only connector-level validation.
- The planning view helps separate content problems from connector problems.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
