# Phase 143: SEO Regression Watch

## Summary
Added a regression watch layer so SEO score drops and new critical issues can be detected, stored, and reviewed instead of being missed between audits.

## Delivered
- Added storage:
  - `seo_regression_state`
  - `seo_regression_runs`
- Added API actions:
  - `seo.regressions.summary`
  - `seo.regressions.run`
- Regression watch evaluates active SEO projects for:
  - score drops of 5+ points
  - new critical checks
  - cleared critical checks
- Regression state tracks prior signatures to avoid repeating the same alert every run
- Added dashboard controls:
  - `Refresh Regressions`
  - `Run Regression Watch`
  - `seoRegressionView`
- Updated API phase marker:
  - `2.29-seo-regression-watch`

## Ops Notes
- Regression watch is detection only in this phase.
- The next step is wiring it into manual and scheduled audit execution so the alerts happen automatically after fresh audits.
