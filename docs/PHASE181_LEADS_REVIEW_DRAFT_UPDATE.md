# Phase 181: Leads Review Draft Update

## Summary
- added bridge support for updating an individual run-review draft before approval
- exposed app API support for authenticated draft updates
- added review draft edit controls to the Leads workspace

## Bridge
- registered `POST /bridge/run-review/draft-update`
- updates the selected `lc_run_drafts` row for the requested `run_id` and `draft_id`
- sanitizes editable fields and normalizes draft status values
- writes a bridge log event with the updated field list

## App API
- added `leads.review.draft.update`
- requires owner session, CSRF, `site_id`, `run_id`, and `draft_id`
- forwards editable fields to the bridge and audits the update request

## UI
- added review draft input fields for business name, city, category, website, phone, email, status, and notes
- added an `Update Review Draft` action in the Leads review workspace
- refreshes the review queue and review detail after a successful update

## Validation
- `php -l includes/class-lc-plugin.php`
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
