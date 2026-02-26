# SEO Suite + Browser Extension MVP Spec

## Goals
- Deliver fast SEO diagnostics and sync findings to platform dashboard.
- Build a clean foundation for future rank and keyword intelligence.

## SEO Suite MVP
1. Site audit checks
2. Project-level issue list
3. Priority scoring (`critical`, `fix_soon`, `nice_to_have`)
4. Historical snapshots per audited URL

## Extension MVP (Chrome/Edge)
- Page-level checks:
  - title/meta presence and length
  - canonical and robots/noindex
  - heading structure
  - image alt coverage
  - internal/external link scan
  - schema detection
- Export:
  - copy summary
  - send to 5N2 dashboard API

## API Contracts (Initial)
- `POST /api/seo/audits`
- `GET /api/seo/projects/{id}/audits`
- `POST /api/seo/extension/session`

## Security
- Token-based auth from dashboard-issued key
- Minimal extension permissions
- User can revoke extension key from settings
