# lead-console

Internal WordPress plugin for the 5N2 Digital lead console (private use only).

## Internal scope
- Proprietary/internal plugin for 5N2 Digital.
- Intended for install on a 5N2-controlled subdomain.
- Not for WordPress marketplace/public distribution.

## Built features (current)
### Core admin
- Dashboard with pipeline and run metrics.
- Leads page (create, import CSV, filter/search, status updates).
- Runs page (queue discovery runs and monitor outputs).
- Duplicates page (review + merge likely duplicates).
- Exports page (download outreach-ready CSV).
- Settings page for free-tier guardrails and API keys.

### Data model
- `lc_leads`
- `lc_runs`
- `lc_run_logs`
- `lc_suppression`

### Workflow + scoring
- Pipeline statuses: New, Verified, Ready, Contacted, Replied, Meeting, Proposal Sent, Won, Lost, Do Not Contact.
- Score (0-100) with contactability/website/listing signals.
- Lead type tagging (A/B/C/E) with “no real website” logic.

### Discovery + cost controls
- Discovery run queue via WP cron (`lc_process_run`).
- Google Places connector support (when live APIs enabled and API key configured).
- Safe default mode keeps live APIs off until explicitly enabled.
- Max places/run enforced from settings.

### Dedupe + suppression
- Duplicate matching by phone/domain on ingest.
- Possible duplicate review/merge screen.
- Suppression enforcement (email/phone/domain/name).

## Install
1. Copy folder to `wp-content/plugins/lead-console`.
2. Activate plugin in WP Admin.
3. Open **Lead Console → Settings**.
4. Confirm domain fragment and free-tier limits.
5. Keep **Enable live API calls** OFF initially.
6. Test manual lead creation + CSV import + status flow.
7. Configure Google key only when ready for controlled discovery runs.

## CSV headers
`business_name, city, category, address, website, phone, email, email_confidence, review_count, rating, status, notes, source_url`

## Important
This is a substantial internal build, but external connectors beyond Google Places and deeper compliance/reporting automations can still be expanded further over time.
