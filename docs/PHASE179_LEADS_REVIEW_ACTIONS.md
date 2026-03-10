# Phase 179: Leads Review Actions

## What changed
- Added bridge save and discard actions for run review drafts.
- Added app-side `leads.review.save` and `leads.review.discard`.
- Added save/discard controls and action result output in the Leads review panel.

## Why it matters
- Pending run drafts can now be resolved from the app control center.
- Saving a review can flow directly into the approved-lead inventory and downstream CRM push path.

## Validation
- `php -l includes/class-lc-plugin.php`
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
