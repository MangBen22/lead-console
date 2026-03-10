# Phase 158: Social Retry Payload Replay

## Summary
- changed the social retry queue to persist the failed draft payload
- changed social retry execution to replay the stored payload instead of rebuilding drafts from current leads
- added retry success entries to the social sync log

## Retry Item Fields
- `drafts`
- `source`
- `schedule_id`
- `last_result`
- `last_attempt_at`

## Version
- bumped plugin version to `2.0.0.178`
