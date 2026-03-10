# Phase 161: Social Retry Automation

## Summary
- extracted social retry execution into a reusable helper
- wired automation runs to process the social retry queue
- extended social automation summaries with retry execution metrics

## Automation Fields
- `retry_processed`
- `retry_succeeded`
- `retry_remaining`
- `retry_run_id`

## Version
- bumped plugin version to `2.0.0.181`
