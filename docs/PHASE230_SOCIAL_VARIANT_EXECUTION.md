# Phase 230 - Social Variant Execution

- `execute_social_connector_sync()` now uses connector-specific prepared draft variants at runtime
- bridge and webhook payloads now receive the shaped provider payload instead of the generic source draft
- connector results now include:
  - `prepared_draft_count`
  - `variant_notes`
  - `payload_preview`
