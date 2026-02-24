# 5N2 Lead Console

Internal WordPress plugin for the 5N2 Digital lead console.

## Created By
This plugin is created by the **5N2 Digital Software Development Team**.

## Ownership and License
- This software, source code, architecture, and related assets are **owned exclusively by 5N2 Digital**.
- This project is **proprietary internal software**.
- Unauthorized copying, redistribution, resale, sublicensing, or public marketplace distribution is prohibited.
- Use is limited to 5N2 Digital-approved environments and clients.

## Internal Scope
- Intended for install on a 5N2-controlled WordPress environment.
- Not for WordPress marketplace/public distribution.

## Features Included
### Core admin
- Dashboard with pipeline and run metrics.
- Leads page (create, import CSV, filter/search, status updates).
- Runs page (queue discovery runs and monitor outputs).
- Duplicates page (review likely duplicates by phone/domain).
- Exports page (download outreach-ready CSV).
- Suppression page (manage email/phone/domain/name blocklist).
- Reports page (funnel + run health summaries).
- Settings page for free-tier guardrails and API keys.

### Data model
- `lc_leads`
- `lc_runs`
- `lc_run_logs`
- `lc_suppression`

### Workflow + scoring
- Pipeline statuses: New, Verified, Ready, Contacted, Replied, Meeting, Proposal Sent, Won, Lost, Do Not Contact.
- Score (0-100) with contactability and listing signals.
- Lead type tagging (A/B/C/E) with "no real website" logic.

### Discovery + cost controls
- Discovery run queue via WP cron (`lc_process_run`).
- Safe default mode keeps live APIs off until explicitly enabled.
- Max places/run enforced from settings.
- Google Places API key field included for controlled connector expansion.

### Dedupe + suppression
- Duplicate checks by phone/domain during CSV import.
- Duplicate review screen (phone/domain counts).
- Suppression enforcement (email/phone/domain/name).
- Suppression admin screen for persistent blocklist management.

## Install
1. Copy this folder to `wp-content/plugins/lead-console`.
2. Ensure the main plugin file is `lead-console.php` in that folder root.
3. Activate **5N2 Lead Console** in WP Admin -> Plugins.
4. Open **Lead Console -> Settings**.
5. Confirm domain fragment and free-tier limits.
6. Keep **Enable live API calls** OFF initially.
7. Add sample leads manually or import CSV.
8. Queue a run in **Lead Console -> Runs**.

## CSV Headers (Required)
`business_name, city, category, address, website, phone, email, email_confidence, review_count, rating, status, notes, source_url`

## Operational Notes
- On activation, required tables are created automatically.
- A cron hook (`lc_process_run`) is scheduled hourly.
- On deactivation, cron processing is unscheduled.
- On uninstall, plugin tables and settings are removed.