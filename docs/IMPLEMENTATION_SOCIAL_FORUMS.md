# Social + Forums Module Implementation Spec

## Goals
- Manage multiple social and forum channels from one console.
- Keep channel-specific UI and features based on connector capabilities.
- Enforce platform terms and permission constraints.

## Connector Model
- One connector per platform account.
- Required fields:
  - `platform` (facebook, instagram, tiktok, x, linkedin, youtube, reddit, discourse, etc.)
  - `account_label`
  - `auth_type` (oauth2, token)
  - `status`
  - `expires_at`
  - `capabilities`

## Capability Flags
- `can_publish_post`
- `can_publish_video`
- `can_schedule`
- `can_read_inbox`
- `can_reply_inbox`
- `can_manage_ads`
- `can_fetch_analytics`

## UI Rules
- Theme and labels adapt to selected platform workspace.
- Unsupported actions are hidden or disabled with explanation.
- Activity feed logs all publish and inbox actions.

## Compliance Rules
- API-first integrations only.
- No prohibited scraping or automation.
- Consent and account ownership must be validated before connect.

## MVP Platforms
- Facebook/Instagram
- LinkedIn
- X
- YouTube
- TikTok
- Reddit
- Discourse
