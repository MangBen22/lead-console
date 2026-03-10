# Phase 216 - Social Retry Detail

- added `social.retry.detail`
- loads one retry queue item by `retry_id`
- returns:
  - normalized retry item
  - connector metadata when available
  - retry drafts
  - retry error details
- added retry detail UI and connector drill-down prefill
