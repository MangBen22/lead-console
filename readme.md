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
- Intelligence page (build profile dossiers per lead).
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
- Discovery modes:
  - `Hybrid` (Google Places API first, then directory fallback)
  - `Google Places API only`
  - `Directory fallback only`

### Dedupe + suppression
- Duplicate checks by phone/domain during CSV import.
- Duplicate review screen (phone/domain counts).
- Suppression enforcement (email/phone/domain/name).
- Suppression admin screen for persistent blocklist management.

## Install
1. Copy this folder to `wp-content/plugins/lead-console`.
2. Ensure the main plugin file is `lead-console.php` in that folder root.
3. Activate **5N2 Lead Console** in WP Admin -> Plugins.
4. Create or edit a front-end page and add shortcode: `[lc_frontend_console]`.
5. Open **Lead Console -> Settings**.
6. Confirm domain fragment and free-tier limits.
7. Keep **Enable live API calls** OFF initially.
8. Add sample leads manually or import CSV.
9. Queue a run in **Lead Console -> Runs**.

## Front-End Portal Flow
- Login form with username/password.
- Required GDPR acceptance at sign-in.
- Welcome message for logged-in user.
- Onboarding tutorial popup with Next/Back flow.
- Checklist step confirmation required before advancing tutorial steps.
- Front-end lead and run actions:
  - Add lead
  - Update lead status
  - Queue run
  - View recent leads and runs

## Design Direction
- Front-end portal styling follows the 5N2 Digital visual direction (dark UI with red/orange/blue brand accent system).

## CSV Headers (Required)
`business_name, city, category, address, website, phone, email, email_confidence, review_count, rating, status, notes, source_url`

## Operational Notes
- On activation, required tables are created automatically.
- A cron hook (`lc_process_run`) is scheduled hourly.
- On deactivation, cron processing is unscheduled.
- On uninstall, plugin tables and settings are removed.

## Google Places API Capture (Step by Step)
1. Open Google Cloud Console.
2. Create/select your project.
3. Enable `Places API`.
4. Go to `APIs & Services -> Credentials`.
5. Create API key.
6. Restrict API key to:
   - Your website/domain
   - Places API only
7. In WordPress open `Lead Console -> Settings`.
8. Paste key into `Google Places API key`.
9. Turn on `Enable live API calls`.
10. Set `Discovery mode` to `Hybrid` or `Google Places API only`.
11. Save settings and queue a run from `Lead Console -> Runs`.

## If Google Places API Is Not Ready
- If API key is empty, plugin logs this in run logs and uses fallback when mode allows.
- Fallback scans configured web directory sources (default list included).
- Default fallback sources are preloaded with quality scores and can be customized in Settings.

## Fallback Directory Sources (Default)
- Google Maps search URL capture
- Yelp
- Yellow Pages
- Better Business Bureau (BBB)
- Chamber of Commerce
- Manta

Use the `Directory sources` setting to override list entries with this format:
`Source Name|https://source.example/search?query={query}&city={city}|85`

## Social Discovery (Low-Risk / Compliant)
- The plugin supports social profile URL enrichment without scraping.
- Recommended mode: `URL discovery only`.
- Uses Google Programmable Search API to discover likely profile URLs for:
  - LinkedIn
  - Facebook
  - Instagram
  - X
  - YouTube
- Stores URL + confidence + source metadata on each lead.

### Social Setup Steps
1. Open `Lead Console -> Settings`.
2. Set `Social discovery mode` to `URL discovery only`.
3. Create/choose a Google Programmable Search Engine and get `cx`.
4. Create Google Programmable Search API key.
5. Enter:
   - `Google Programmable Search API key`
   - `Google Programmable Search Engine ID (cx)`
6. Save settings.
7. Queue a run from `Lead Console -> Runs`.

### Compliance Guardrails
- No direct LinkedIn scraping.
- No automated login/bot actions on social networks.
- Official API mode is reserved for future provider-approved OAuth integrations.

## Lead Intelligence Dossier (Person + Company Context)
- New table: `lc_lead_profiles`
- Captures enriched lead-level intelligence:
  - possible emails (list)
  - selected primary email (single best candidate)
  - social profile URLs (LinkedIn/Facebook/Instagram/X/YouTube)
  - likely people/positions when discoverable (e.g. Founder/Owner/CEO patterns)
  - company profile signals from website metadata
  - review signals (Google Places, if available)
  - hiring/job signals (Indeed signal via search)
  - completeness and confidence scoring

### How To Use Intelligence (Step by Step)
1. Open `Lead Console -> Intelligence`.
2. Click `Enrich 25 Most Recent Leads` or enrich one lead at a time.
3. Wait for enrichment to complete.
4. Review:
   - Primary email selected from all discovered emails
   - Social URLs
   - Completeness and confidence scores
5. Re-run enrichment after updating API keys/settings for better results.

### Data Availability Expectations
- Some leads will have complete profiles, others partial profiles.
- Output depends on public web availability and configured APIs.
