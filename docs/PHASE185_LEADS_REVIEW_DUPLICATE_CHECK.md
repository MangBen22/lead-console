# Phase 185: Leads Review Duplicate Check

## Summary
- added duplicate detection for pending review drafts against already approved leads
- checks website, email, and business name plus city matches
- surfaced duplicate results directly in the Leads review workspace

## App API
- added `leads.review.duplicates`
- loads the review draft preview for a selected site and run
- compares each draft against approved leads already collected across bridge sites

## UI
- added `Check Review Duplicates`
- added a duplicate-check result panel in the Leads review section
- duplicate checks also refresh automatically after loading review detail

## Matching Rules
- exact website match
- exact email match
- exact `business_name + city` match

## Validation
- `php -l app-site/api/index.php`
- `php -l app-site/index.php`
- `php -l lead-console.php`
- `node --check app-site/assets/app.js`
