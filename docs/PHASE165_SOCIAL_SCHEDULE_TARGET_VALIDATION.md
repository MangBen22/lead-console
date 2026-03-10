# Phase 165: Social Schedule Target Validation

## Summary
- added validation for schedule target connectors
- blocked schedule saves when selected connectors are invalid for scheduling
- added a schedule-target validation panel in the Social dashboard

## Validation Signals
- `connector_not_found`
- `connector_inactive`
- `schedule_capability_missing`
- `credential_expired`
- `no_active_schedulable_connectors`

## API
- added `social.schedule.targets.validate`

## Version
- bumped plugin version to `2.0.0.185`
