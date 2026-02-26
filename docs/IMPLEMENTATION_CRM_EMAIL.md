# CRM + Email Module Implementation Spec

## Goals
- Sync approved leads to internal or external CRMs.
- Provide robust SMTP operations with health checks and guided verification.
- Keep audit trails for all connector and email actions.

## Connector Types
1. `wordpress_plugin`
2. `external_api`
3. `csv_export_bridge`

## Connector Record Fields
- `connector_id`
- `provider` (fluentcrm, hubspot, salesforce, custom)
- `type`
- `status` (planned, active, paused, error)
- `auth_mode` (api_key, oauth2, token)
- `capabilities` (create_contact, update_contact, list_segments, send_event)
- `last_sync_at`
- `last_error`

## SMTP Lifecycle
1. User enters SMTP settings.
2. System runs connection probe (`lc_smtp_health_check`).
3. If successful: status `green`, show test email button.
4. Send test email to user-provided address.
5. User confirms received (`yes/no`).
6. Save final verified state with timestamp and actor.

## Cron
- Every 30 minutes:
  - check SMTP connectivity
  - write status to system logs
  - trigger notification event on failure state changes

## Event Names
- `smtp.connection.ok`
- `smtp.connection.failed`
- `smtp.test.sent`
- `smtp.test.failed`
- `smtp.test.user_confirmed`

## Error Handling
- Capture mailer daemon or provider errors.
- Store normalized failure codes.
- Show human-actionable message in Settings and Notifications.

## Security
- Keep credentials masked in UI.
- Encrypt at-rest if platform key management is available.
- Restrict settings edit by admin role only.
