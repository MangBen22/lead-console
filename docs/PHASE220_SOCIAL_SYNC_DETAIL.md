# Phase 220 - Social Sync Detail

- added `social.push.detail`
- loads one sync log item by `sync_id`
- returns:
  - normalized sync item
  - resolved connector metadata
  - result and error counts
  - retry and schedule linkage flags
- added sync detail UI and retry drill-down prefill
