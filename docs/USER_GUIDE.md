# 5N2 Lead Console User Guide

## Overview
Welcome to **5N2 Digital Lead Console**.  
This guide explains what each section does and how to use it safely.

## Screenshot Index
- `docs/screenshots/01-login.png` : Login screen
- `docs/screenshots/02-dashboard.png` : Frontend console dashboard
- `docs/screenshots/03-leads.png` : Leads section
- `docs/screenshots/04-runs.png` : Runs section
- `docs/screenshots/05-compliance.png` : Compliance section
- `docs/screenshots/06-tutorial.png` : Tutorial popup
- `docs/screenshots/07-users-admin.png` : Users admin page
- `docs/screenshots/08-logs-admin.png` : Logs admin page
- `docs/screenshots/09-intelligence-admin.png` : Intelligence page

## 1. Login and Security
- Use your assigned username/password.
- Use `Show` on password field to verify typing if needed.
- If password is forgotten:
  - Submit your email in `Forgot Password`.
  - If email is valid, reset link is sent and is valid for 5 minutes.
  - If invalid email, an error message appears.

## 2. Password Change and Revoke
- After successful reset, the system sends:
  - Password-change confirmation email.
  - Revoke link valid for 5 minutes.
- If revoke link is used:
  - Account is locked immediately.
  - Super admin is notified.
  - Only super admin can unlock/reset manually.

## 3. Frontend Sections
### Leads
- Add lead fields: business name, city, category, website, phone, email.
- Save lead to add to pipeline.

### Runs
- Add search query and city.
- Set max places.
- Queue run for discovery processing.

### Compliance
- Use only lawful/public data and approved API sources.
- Do not run prohibited scraping or unauthorized automation.

## 4. Tutorial
- Click `Start Tutorial`.
- Use `Next/Back` or keyboard arrows.
- Tutorial highlights target sections.
- Tutorial popup can be dragged by header.

## 5. Admin Pages (Super Admin)
### Users
- View user avatar, username, email, role, lock status.
- Manual password reset.
- Lock/unlock users.

### Logs
- Centralized log stream for:
  - Authentication activity
  - GDPR confirmations
  - Lead and run actions
  - System/runtime errors
- Logs are super-admin only.

### Intelligence
- Enrich lead profiles.
- Review completeness and confidence.

## 6. Known Notes
- Data richness varies by public availability and configured APIs.
- Some results are complete, others partial.

## 7. Screenshot Update Process
1. Capture screenshot from each listed section.
2. Save using exact filenames in `docs/screenshots/`.
3. Commit and push with patch version bump.
