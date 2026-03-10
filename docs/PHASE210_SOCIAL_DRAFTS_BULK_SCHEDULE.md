# Phase 210 - Social Drafts Bulk Schedule

- added `social.drafts.bulk_schedule`
- bulk schedules the current filtered draft page
- supports:
  - optional connector override
  - optional bulk start time
  - interval minutes between queued items
- each draft still falls back to recommended ready connectors when no connector override is supplied
- added bulk scheduling UI and refresh of schedule and activity views after execution
