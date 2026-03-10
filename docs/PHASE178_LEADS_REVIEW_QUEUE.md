# Phase 178: Leads Review Queue

## What changed
- Added bridge routes for run review queue and run review detail.
- Added app-side aggregation for pending review runs across bridge sites.
- Added Leads review queue and review detail panels in the app workspace.

## Why it matters
- The app can now see real pending run drafts instead of only approved leads.
- This creates the foundation for app-side save/discard review actions in the next phase.

## Validation
- `php -l includes/class-lc-plugin.php`
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
