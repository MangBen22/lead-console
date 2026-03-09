# Phase 123: Social Schedule Queue

## Summary
Added a scheduled post queue so Social can store manual publish jobs, target specific connectors, and run due items through the same sync adapters.

## Delivered
- Added API actions:
  - `social.schedule.list`
  - `social.schedule.save`
  - `social.schedule.delete`
  - `social.schedule.run`
- Added storage key:
  - `social_schedule_queue`
- Scheduled posts now support:
  - `schedule_id`
  - `title`
  - `message`
  - `url`
  - `connector_ids`
  - `scheduled_for`
  - `status`
  - `attempts`
- Due schedule runs write into Social sync log and enqueue retries on connector failures.
- Added dashboard controls:
  - `socialScheduleQueue`
  - `Save Scheduled Post`
  - `Delete Scheduled Post`
  - `Run Due Scheduled Posts`
- Updated API phase marker:
  - `2.09-social-schedule-queue`

## Ops Notes
- Leave `connector_ids` blank to target every active social connector when a schedule runs.
