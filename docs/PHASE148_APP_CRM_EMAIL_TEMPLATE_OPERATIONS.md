# Phase 148: App CRM Email Template Operations

## Summary
Added app-side email template loading and saving so bridge-connected sites can be managed from the app without opening WordPress settings directly.

## Delivered
- Added API actions:
  - `crm.email_templates.get`
  - `crm.email_templates.save`
- Added CRM helper:
  - `crm_email_templates_fetch()`
- `crm.summary` now reports:
  - `email_template_sites`
- Added CRM UI panel:
  - `Refresh Templates`
  - `Load Selected Template`
  - `Save Selected Template`
- Added app-side views and fields:
  - `crmEmailTemplatesSummary`
  - `crmEmailTemplatesResult`
  - `crmEmailTemplateSiteId`
  - `crmEmailTemplateKey`
  - `crmEmailTemplateSubject`
  - `crmEmailTemplateBody`
- Updated API phase marker:
  - `2.32-crm-email-template-ops`

## Ops Notes
- This phase edits one selected template at a time for a chosen bridge site.
- The bridge remains the persistence layer and still uses plugin-side sanitization.
