# Phase 156: Social Watch

## Summary
- added a persisted social connector watch with status history and manual runs
- integrated social watch into automation summaries and notifications
- added a Social Watch panel in the app dashboard

## Watch States
- `ready`
- `blocked`
- `expiring_soon`
- `expired`

## API
- added `social.watch.summary`
- added `social.watch.run`

## Automation
- social automation now records blocked and expired connector counts
- social automation now records the latest social watch run ID

## Version
- bumped plugin version to `2.0.0.176`
