# Phase 125: Social Inbox Workflow

## Summary
Added a basic Social inbox workflow with thread listing and reply handling so the module now covers the first chat-support use case.

## Delivered
- Added API actions:
  - `social.inbox.list`
  - `social.inbox.reply`
- Added storage key:
  - `social_inbox_threads`
- Added inbox thread seeding for active connectors that support:
  - `can_read_inbox`
- Reply flow validates:
  - connector existence
  - `can_reply_inbox` capability
- `social.summary` now returns:
  - `retry_backlog`
  - inbox-backed `unread_conversations`
- Added dashboard controls:
  - `Refresh Social Inbox`
  - `socialInboxThreads`
  - `Reply to Thread`
- Updated API phase marker:
  - `2.11-social-inbox-workflow`

## Ops Notes
- Inbox threads are currently bridge-safe internal records; this is the base layer before platform-native inbox adapters are added.
