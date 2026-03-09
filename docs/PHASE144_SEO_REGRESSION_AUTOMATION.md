# Phase 144: SEO Regression Automation

## Summary
Wired the regression watch into actual audit execution so SEO regression detection runs automatically after new audits instead of depending on a manual button.

## Delivered
- Manual `seo.audit.run` now returns the fresh regression watch result
- Automation runs now execute SEO regression watch after SEO audits complete
- Automation summary now records:
  - `seo.regressions`
  - `seo.regression_run_id`
- Updated API phase marker:
  - `2.30-seo-regression-automation`

## Ops Notes
- This phase turns the regression watch into an operational control, not just a reporting panel.
- Scheduler-driven SEO audits can now raise regression notifications as part of the same automation cycle.
