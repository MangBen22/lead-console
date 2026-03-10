# Phase 173: Leads Push Plan

## What changed
- Added `leads_push_plan_snapshot()` to summarize approved leads against active CRM connectors.
- Added `leads.push.plan` for a non-destructive delivery preview.
- Added a Leads Push Plan panel in the app workspace.

## Why it matters
- Operators can now verify whether approved leads have active CRM destinations before running sync.
- The Leads module exposes delivery readiness instead of relying only on the CRM section.

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
