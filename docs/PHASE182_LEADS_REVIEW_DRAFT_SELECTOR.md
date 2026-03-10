# Phase 182: Leads Review Draft Selector

## Summary
- added a review draft selector to the Leads review workspace
- auto-filled the draft editor from the loaded review detail payload
- kept the draft id field in sync with the selected review draft

## UI
- added a `Review Draft Selector` dropdown beside the draft id field
- each option shows the draft id, business name, and city for faster identification
- if no draft id is preselected, the first loaded draft becomes the active editor record

## App Behavior
- `loadLeadsReviewDetail()` now captures the review draft preview list
- the draft selector is populated from the latest review detail response
- selecting a draft updates:
  - draft id
  - business name
  - city
  - category
  - website
  - phone
  - email
  - status
  - notes

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
