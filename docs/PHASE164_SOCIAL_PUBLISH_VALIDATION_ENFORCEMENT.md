# Phase 164: Social Publish Validation Enforcement

## Summary
- enforced Social draft validation inside connector sync execution
- blocked incompatible drafts consistently across sync, schedule, retry, and automation
- returned validation warnings and issue codes with connector sync results

## Result Fields
- `warnings`
- `warning_codes`
- `validation`

## Version
- bumped plugin version to `2.0.0.184`
