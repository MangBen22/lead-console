# Phase 174: Leads Push Controls

## What changed
- Added Leads-side controls for CRM sync and CRM retry queue execution.
- Added a dedicated Leads push result panel.
- Added shared Leads/CRM module card refresh after push actions.

## Why it matters
- Operators can now execute lead delivery directly from the Leads workspace.
- Push execution and retry handling are no longer isolated inside the CRM section.

## Validation
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
