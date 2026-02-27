# Phase 10 SEO Operational Module

## Delivered
- SEO project endpoints:
  - `seo.projects.list`
  - `seo.projects.save`
  - `seo.projects.delete`
- SEO audit endpoints:
  - `seo.audit.run`
  - `seo.audits.list`
- Browser extension intake:
  - `seo.extension.intake` (public with `X-SEO-Extension-Key`)
  - `seo.extension.events.list`

## Dashboard UI
- SEO project form (save/delete)
- Run SEO audit action
- SEO audit history panel
- SEO extension events panel

## Audit Runner Checks
- Target URL configured
- HTTPS check
- Homepage reachability
- `robots.txt` availability
- `sitemap.xml` availability

## Config Requirement
Set `seo_extension_ingest_key` in `app-site/config.php` to accept extension intake requests.

## Next
1. Add page-level analyzer expansion (title/meta/headings/schema checks).
2. Add rank tracking datastore.
3. Add extension auth session endpoint and project auto-mapping.
