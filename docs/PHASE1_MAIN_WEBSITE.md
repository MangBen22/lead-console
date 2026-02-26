# Phase 1.1 Main Website Build

## Delivered
- New `app-site` scaffold for `app.5n2digital.com`
- Session-based login shell for development
- Control-center layout with five module entry points
- Notification panel and API status fetch
- Minimal API endpoint stubs under `app-site/api/index.php`

## Why this first
- Gives a stable home for all future modules.
- Keeps plugin and web platform separation clear.
- Deployable quickly on Hostinger PHP without framework lock-in.

## Next in Phase 1
1. Replace demo auth with production auth and role policy.
2. Add database layer for accounts, sites, connectors, notifications.
3. Add plugin bridge API endpoints for secure site pairing.
4. Add activity log and audit event stream.
